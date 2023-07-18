<?php

namespace Gems\Command;

use Gems\Db\Migration\TableRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:table', description: 'Show and create new Database tables')]
class RunTables extends RunMigrationAbstract
{
    protected string $topic = 'table';

    protected string $topicPlural = 'tables';
    public function __construct(
        protected TableRepository $tableRepository)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->tableRepository->hasMigrationTable()) {
            $io = new SymfonyStyle($input, $output);
            $result = $io->confirm('Migration table missing. Should the migration table be created?');
            if ($result) {
                $this->tableRepository->createMigrationTable();
            }
        }

        return parent::execute($input, $output);
    }

    protected function getModel(): DataReaderInterface
    {
        return $this->tableRepository->getModel();
    }

    protected function repositoryRunMigration(array $info): void
    {
        $this->tableRepository->createTable($info);
    }
}
