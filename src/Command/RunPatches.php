<?php

namespace Gems\Command;

use Gems\Db\Migration\PatchRepository;
use Gems\Db\Migration\TableRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:patch', description: 'Show and run Database patches')]
class RunPatches extends RunMigrationAbstract
{
    protected string $topic = 'patch';

    protected string $topicPlural = 'patches';
    public function __construct(
        protected TableRepository $tableRepository,
        protected PatchRepository $patchRepository)
    {
        parent::__construct();
    }

    protected function createOrder(): int
    {
        $now = new \DateTimeImmutable();
        return $now->format('YmdHis');
    }

    protected function createPatch(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $io->ask('Name:');
        $addDown = $io->confirm('Add revert file (down)', true);

        $directories = array_column($this->patchRepository->getPatchesDirectories(), 'path');
        $first = reset($directories);

        $targetDir = $io->choice('Where should the patch be placed?', $directories, $first);

        $order = $this->createOrder();

        $fileUp = $targetDir . DIRECTORY_SEPARATOR . $order . '.' . $name . '.up.sql';
        $fileDown = $targetDir . DIRECTORY_SEPARATOR . $order . '.' . $name . '.down.sql';

        $result = touch($fileUp);
        if ($addDown) {
            touch($fileDown);
        }

        if ($result) {
            $message = sprintf('Patch %s created', realpath($fileUp));
            if ($addDown) {
                $message = sprintf('Patches %s created', join(', ', [realpath($fileUp), realpath($fileDown)]));
            }
            $io->success($message);
            return Command::SUCCESS;
        }
        return Command::FAILURE;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->tableRepository->hasMigrationTable()) {
            $io = new SymfonyStyle($input, $output);
            $result = $io->confirm('Migration table missing. Should the migration table be created?');
            if ($result) {
                $this->tableRepository->createMigrationTable();
            }
        }

        $id = $input->getArgument('id');

        $result = match($id) {
            'create' => $this->createPatch($input, $output),
            'new-order' => $this->getNewOrder($input, $output),
            'baseline' => $this->setBaseline($input, $output),
            default => parent::execute($input, $output),
        };
        return $result;
    }

    protected function getModel(): DataReaderInterface
    {
        return $this->patchRepository->getModel();
    }

    protected function getNewOrder(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->createOrder());

        return Command::SUCCESS;
    }

    protected function repositoryRunMigration(array $info): void
    {
        $this->patchRepository->runPatch($info);
    }

    protected function setBaseline(InputInterface $input, OutputInterface $output): int
    {
        $this->patchRepository->setBaseline();
        return Command::SUCCESS;
    }
}
