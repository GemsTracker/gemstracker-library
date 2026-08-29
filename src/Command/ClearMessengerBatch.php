<?php

declare(strict_types=1);

namespace Gems\Command;

use DateInterval;
use Exception;
use Gems\Messenger\Batch\BatchStoreInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear-messenger-batch', description: 'Clear old messenger batch data')]
class ClearMessengerBatch extends Command
{

    public function __construct(
        private readonly ContainerInterface $container,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument(
            name: 'days',
            mode: InputArgument::OPTIONAL,
            description: 'Age in days of items to remove. Can also be a DateInterval notation (e.g. P7D)',
            default: '7',
        );

        $this->addArgument(
            name: 'group',
            mode: InputArgument::OPTIONAL,
            description: 'Optional Group to clear',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $removeInterval = $this->getInterval($input);

        /** @var BatchStoreInterface $batchStore */
        $batchStore = $this->container->get(BatchStoreInterface::class);

        $batchStore->clearOldBatches($removeInterval);

        return Command::SUCCESS;
    }

    protected function getInterval(InputInterface $input): DateInterval
    {
        $days = $input->getArgument('days');
        if (ctype_digit($days)) {
            return new DateInterval('P' . (int)$days . 'D');
        }

        try {
            $interval = new DateInterval($days);
            return $interval;
        } catch (Exception $e) {
            throw new Exception(sprintf('Input days (%s) is not an int or a DateInterval string', $days));
        }
    }
}