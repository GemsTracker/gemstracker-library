<?php

namespace Gems\Command;

use Gems\Db\Migration\PatchRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:patches', description: 'Show and run Database patches')]
class RunPatches extends RunMigrationAbstract
{
    protected string $topic = 'patch';

    protected string $topicPlural = 'patches';
    public function __construct(
        protected PatchRepository $patchRepository)
    {
        parent::__construct();
    }

    protected function getModel(): DataReaderInterface
    {
        return $this->patchRepository->getModel();
    }

    protected function repositoryRunMigration(array $info): void
    {
        $this->patchRepository->runPatch($info);
    }
}
