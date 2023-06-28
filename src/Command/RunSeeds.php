<?php

namespace Gems\Command;

use Gems\Db\Migration\SeedRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:seeds', description: 'Show and run Database seeds')]
class RunSeeds extends RunMigrationAbstract
{
    protected string $topic = 'seed';

    protected string $topicPlural = 'seeds';
    public function __construct(
        protected SeedRepository $seedRepository)
    {
        parent::__construct();
    }

    protected function getModel(): DataReaderInterface
    {
        return $this->seedRepository->getModel();
    }

    protected function repositoryRunMigration(array $info): void
    {
        $this->seedRepository->runSeed($info);
    }
}
