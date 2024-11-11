<?php

namespace Gems\Command;

use Gems\Db\Migration\MigrationModelFactory;
use Gems\Db\Migration\SeedRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:seed', description: 'Show and run Database seeds')]
class RunSeeds extends RunMigrationAbstract
{
    protected string $topic = 'seed';

    protected string $topicPlural = 'seeds';
    public function __construct(
        protected SeedRepository $seedRepository,
        protected readonly MigrationModelFactory $migrationModelFactory,
    )
    {
        parent::__construct();
    }

    protected function getModel(): DataReaderInterface
    {
        return $this->migrationModelFactory->createModel($this->seedRepository);
    }

    protected function repositoryRunMigration(array $info): void
    {
        $this->seedRepository->runSeed($info);
    }
}
