<?php

namespace Gems\Command;

use Gems\Db\Migration\TableRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:tables', description: 'Show and create new Database tables')]
class RunTables extends RunMigrationAbstract
{
    protected string $topic = 'table';

    protected string $topicPlural = 'tables';
    public function __construct(
        protected TableRepository $tableRepository)
    {
        parent::__construct();
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
