<?php

namespace Gems\Command;

use Gems\Db\Migration\PatchRepository;
use Gems\Db\Migration\SeedRepository;
use Gems\Db\Migration\TableRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zalt\Model\Data\DataReaderInterface;

#[AsCommand(name: 'db:migrate', description: 'Show and create all migrations')]
class RunMigrations extends Command
{
    protected array $displayColumns = [
        'index' => 'Index',
        'name' => 'Name',
        'module' => 'Module',
        'description' => 'Description',
        'order' => 'Order',
    ];

    protected string $topic = 'migration';

    protected string $topicPlural = 'migrations';
    public function __construct(
        protected PatchRepository $patchRepository,
        protected SeedRepository $seedRepository,
        protected TableRepository $tableRepository
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('command', InputArgument::OPTIONAL, sprintf('use \'all\' Directy run all migrations'));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('all');

        $result = match($command) {
            'all', '~' => $this->runAll($input, $output),
            'init', 'start' => $this->initDatabase($input, $output),
            'show' => $this->showAll($input, $output),
            default => $this->showAll($input, $output),
        };

        return $result;
    }

    protected function createTables(array $tables, SymfonyStyle $io): void
    {
        foreach($tables as $tableInfo) {
            try {
                $this->tableRepository->createTable($tableInfo);
                $io->success(sprintf('%s %s successfully executed', 'Table', $tableInfo['name']));
            } catch (\Exception $e) {
                $io->error(sprintf('%s %s failed.. %s', 'Table', $tableInfo['name'], $e->getMessage()));
            }
        }
    }

    protected function initDatabase(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tables = $this->getTables();
        $seeds = $this->getSeeds();

        $this->createTables($tables, $io);
        $this->patchRepository->setBaseline();

        $this->runSeeds($seeds, $io);

        return Command::SUCCESS;
    }

    protected function runAll(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tables = $this->getTables();
        $patches = $this->getPatches();
        $seeds = $this->getSeeds();

        $this->createTables($tables, $io);
        $this->runPatches($patches, $io);
        $this->runSeeds($seeds, $io);

        return Command::SUCCESS;
    }

    protected function runPatches(array $patches, SymfonyStyle $io): void
    {
        foreach($patches as $patchInfo) {
            try {
                $this->patchRepository->runPatch($patchInfo);
                $io->success(sprintf('%s %s successfully executed', 'Patch', $patchInfo['name']));
            } catch (\Exception $e) {
                $io->error(sprintf('%s %s failed.. %s', 'Patch', $patchInfo['name'], $e->getMessage()));
            }
        }
    }

    protected function runSeeds(array $seeds, SymfonyStyle $io): void
    {
        foreach($seeds as $seedInfo) {
            try {
                $this->seedRepository->runSeed($seedInfo);
                $io->success(sprintf('%s %s successfully executed', 'Seed', $seedInfo['name']));
            } catch (\Exception $e) {
                $io->error(sprintf('%s %s failed.. %s', 'Seed', $seedInfo['name'], $e->getMessage()));
            }
        }
    }

    protected function showAll(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tables = $this->getTables();
        $this->showItems($tables, 'tables', $output, $io);

        $patches = $this->getPatches();
        $this->showItems($patches, 'patches', $output, $io);

        $seeds = $this->getSeeds();
        $this->showItems($seeds, 'seeds', $output, $io);

        $result = $io->confirm('Run all migrations?', false);
        if (!$result) {
            return Command::SUCCESS;
        }
        return $this->runAll($input, $output);
    }

    protected function showItems(array $items, string $topic, OutputInterface $output, SymfonyStyle $io): void
    {
        $io->title(ucfirst($topic));
        if (count($items) === 0) {
            $output->writeln(sprintf('No %s found', $topic));
            $output->writeln('');
            return;
        }

        $displayInfo = [];
        foreach($items as $index => $item) {
            $displayInfo[] = ['index' => $index + 1] + array_intersect_key($item, $this->displayColumns);
        }

        $table = new Table($output);
        $table->setHeaders($this->displayColumns);
        $table->setRows($displayInfo);

        $table->render();
    }

    protected function getPatches(): array
    {
        $model = $this->patchRepository->getModel();
        return $model->load(['status' => ['new', 'error']], ['order']);
    }

    protected function getSeeds(): array
    {
        $model = $this->seedRepository->getModel();
        return $model->load(['status' => ['new', 'error']], ['order']);
    }

    protected function getTables(): array
    {
        $model = $this->tableRepository->getModel();
        return $model->load(['status' => ['new', 'error']], ['order']);
    }
}
