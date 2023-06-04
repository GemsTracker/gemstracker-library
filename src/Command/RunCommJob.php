<?php

namespace Gems\Command;

use Gems\Console\ConsoleSettings;
use Gems\Messenger\Message\CommJob;
use Gems\Repository\CommJobRepository;
use Gems\Util\Lock\CommJobLock;
use Gems\Util\Lock\MaintenanceLock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'comm-job:run', description: 'Run the communication job, or add it to the queue')]
class RunCommJob extends Command
{
    public function __construct(
        protected CommJobLock $commJobLock,
        protected MaintenanceLock $maintenanceLock,
        protected MessageBusInterface $messageBus,
        protected CommJobRepository $commJobRepository,
        protected ConsoleSettings $consoleSettings,
        protected array $config,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->maintenanceLock->isLocked()) {
            $output->writeln('<error>Cannot run cron job in maintenance mode.</error>');
            return static::FAILURE;
        }
        if ($this->commJobLock->isLocked()) {
            $output->writeln('<error>Cron jobs turned off.</error>');
            return static::FAILURE;
        }

        $this->consoleSettings->setConsoleUser();

        $jobs = $this->commJobRepository->getActiveJobs();

        foreach($jobs as $jobData) {
            $commJobMessage = new CommJob((int)$jobData['gcj_id_job']);
            $this->messageBus->dispatch($commJobMessage);
        }

        $output->writeln(sprintf('<info>Added %d jobs</info>', count($jobs)));

        return static::SUCCESS;
    }
}