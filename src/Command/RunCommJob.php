<?php

namespace Gems\Command;

use Gems\Console\ConsoleSettings;
use Gems\Messenger\Batch\MessengerBatchRepository;
use Gems\Messenger\Message\CommJob;
use Gems\Repository\CommJobRepository;
use Gems\Util\Lock\CommJobLock;
use Gems\Util\Lock\MaintenanceLock;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsCommand(name: 'comm-job:run', description: 'Run the communication job, or add it to the queue')]
class RunCommJob extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::OPTIONAL, ' comm job ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var MaintenanceLock $maintenanceLock
         */
        $maintenanceLock = $this->container->get(MaintenanceLock::class);

        if ($maintenanceLock->isLocked()) {
            $output->writeln('<error>Cannot run cron job in maintenance mode.</error>');
            return static::FAILURE;
        }

        /**
         * @var CommJobLock $commJobLock
         */
        $commJobLock = $this->container->get(CommJobLock::class);
        if ($commJobLock->isLocked()) {
            $output->writeln('<error>Cron jobs turned off.</error>');
            return static::FAILURE;
        }

        /**
         * @var ConsoleSettings $consoleSettings
         */
        $consoleSettings = $this->container->get(ConsoleSettings::class);
        $consoleSettings->setConsoleUser();

        /** @var CommJobRepository $commJobRepository */
        $commJobRepository = $this->container->get(CommJobRepository::class);
        $jobs = $commJobRepository->getAutomaticJobs();

        $id = $input->getArgument('id');
        if ($id !== null) {
            $jobs = $commJobRepository->getActiveJobs();
            $jobs = array_filter($jobs, function ($job) use ($id) {
                return $job['gcj_id_job'] == $id;
            });
            if (!count($jobs)) {
                $output->writeln('<error>Job with ID ' . $id .  ' not found.</error>');
            }
        }

        $commJobRepository->clearTokenQueue();

        /** @var MessengerBatchRepository $messengerBatchRepository */
        $messengerBatchRepository = $this->container->get(MessengerBatchRepository::class);
        $now = new \DateTimeImmutable();
        $batch = $messengerBatchRepository->createBatch('CommJob ' . $now->format('Y-m-d H:i:s'), 'CommJob', true);
        foreach($jobs as $jobData) {
            $commJobMessage = new CommJob($jobData);
            $batch->addMessage($commJobMessage);
        }

        $messengerBatchRepository->dispatch($batch);

        $output->writeln(sprintf('<info>Dispatched %d jobs</info>', count($jobs)));

        /** @var MessengerBatchRepository $messengerBatchRepository */
        $messengerBatchRepository = $this->container->get(MessengerBatchRepository::class);
        $messages = $messengerBatchRepository->getBatchInfoList($batch->batchId);
        foreach($messages as $message) {
            $output->writeln(sprintf('<info>%s</info>', $message));
        }

        return static::SUCCESS;
    }
}