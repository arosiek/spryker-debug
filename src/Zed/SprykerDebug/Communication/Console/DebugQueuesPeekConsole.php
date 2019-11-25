<?php

namespace Inviqa\Zed\SprykerDebug\Communication\Console;

use Inviqa\Zed\SprykerDebug\Communication\Model\Cast;
use RuntimeException;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Inviqa\Zed\SprykerDebug\Communication\SprykerDebugCommunicationFactory getFactory()
 */
class DebugQueuesPeekConsole extends Console
{
    private const ARG_NAME = 'peek';
    private const OPT_VHOST = 'vhost';
    const OPT_JSON = 'json';

    public function configure()
    {
        $this->setName('debug:queues:peek');
        $this->addArgument(self::ARG_NAME, InputArgument::REQUIRED, 'Name of queue to peak into');
        $this->addOption(self::OPT_VHOST, null, InputOption::VALUE_REQUIRED, 'Filter by vhost', '%2f');
        $this->addOption(self::OPT_JSON, null, InputOption::VALUE_NONE, 'Pretty print JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getFactory()->getRabbitClient();

        $messages = $client->peek(
            Cast::toString($input->getOption(self::OPT_VHOST)),
            Cast::toString($input->getArgument(self::ARG_NAME))
        );

        foreach ($messages as $message) {
            $output->writeln($this->formatPayload($input, $message->payload()), OutputInterface::OUTPUT_RAW);
        }
    }

    private function formatPayload(InputInterface $input, string $message): string
    {
        if ($input->getOption(self::OPT_JSON)) {
            $decoded = json_decode($message);
            if (false === $decoded) {
                throw new RuntimeException(sprintf(
                    'Could not decode JSON: "%s"', json_last_error_msg()
                ));
            }

            $encoded = json_encode($decoded, JSON_PRETTY_PRINT);
            if (false === $encoded) {
                throw new RuntimeException(
                    'Could not encode JSON'
                );
            }

            return $encoded;
        }

        return $message;
    }
}
