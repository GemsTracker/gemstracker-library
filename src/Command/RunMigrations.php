<?php

namespace Gems\Command;

use Gems\Cache\HelperAdapter;
use Gems\Db\Migration\MigrationModelFactory;
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
use Zalt\Model\Sql\Laminas\CachedLaminasRunner;

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
        protected TableRepository $tableRepository,
        protected readonly MigrationModelFactory $migrationModelFactory,
        protected readonly HelperAdapter $cache,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('type', InputArgument::OPTIONAL, sprintf('use \'all\' Directy run all migrations'));
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

        $command = $input->getArgument('type');

        $result = match($command) {
            'all', '~' => $this->runAll($input, $output),
            'init', 'start' => $this->initDatabase($input, $output),
            'show' => $this->showAll($input, $output),
            default => $this->showAll($input, $output),
        };

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

    protected function createTables(array $tables, SymfonyStyle $io): bool
    {
        foreach($tables as $tableInfo) {
            try {
                $this->tableRepository->createTable($tableInfo);
                $io->success(sprintf('%s %s successfully executed', 'Table', $tableInfo['name']));
            } catch (\Exception $e) {
                $io->error(sprintf('%s %s failed.. %s', 'Table', $tableInfo['name'], $e->getMessage()));
                return false;
            }
        }

        return true;
    }

    protected function initDatabase(InputInterface $input, OutputInterface $output): bool
    {
        $io = new SymfonyStyle($input, $output);

        $tables = $this->getTables();
        $seeds = $this->getSeeds();

        if (!$this->createTables($tables, $io)) {
            return false;
        }
        $this->patchRepository->setBaseline();

        return $this->runSeeds($seeds, $io);
    }

    protected function runAll(InputInterface $input, OutputInterface $output): bool
    {
        $io = new SymfonyStyle($input, $output);

        $tables = $this->getTables();
        $patches = $this->getPatches();
        $seeds = $this->getSeeds();

        if (!$this->createTables($tables, $io)) {
            return false;
        }
        if (!$this->runPatches($patches, $io)) {
            return false;
        }

        return $this->runSeeds($seeds, $io);
    }

    protected function runPatches(array $patches, SymfonyStyle $io): bool
    {
        $ret = true;
        foreach($patches as $patchInfo) {
            try {
                $this->patchRepository->runPatch($patchInfo);
                $io->success(sprintf('%s %s successfully executed', 'Patch', $patchInfo['name']));
            } catch (\Exception $e) {
                if ($this->patchRepository->lastSql) {
                    $io->error(sprintf("While running patch %s for the SQL statement:\n\n%s", $patchInfo['name'], $this->patchRepository->lastSql));
                }
                $io->error(sprintf('%s %s failed.. %s', 'Patch', $patchInfo['name'], $e->getMessage()));
                $ret = false;
            }
        }
        $this->cache->invalidateTags([CachedLaminasRunner::TAG]);

        return $ret;
    }

    protected function runSeeds(array $seeds, SymfonyStyle $io): bool
    {
        foreach($seeds as $seedInfo) {
            try {
                $this->seedRepository->runSeed($seedInfo);
                $io->success(sprintf('%s %s successfully executed', 'Seed', $seedInfo['name']));
            } catch (\Exception $e) {
                if ($this->seedRepository->lastSql) {
                    $io->error(sprintf("While running patch %s for the SQL statement:\n\n%s", $seedInfo['name'], $this->seedRepository->lastSql));
                }
                $io->error(sprintf('%s %s failed.. %s', 'Seed', $seedInfo['name'], $e->getMessage()));
                return false;
            }
        }

        return true;
    }

    protected function showAll(InputInterface $input, OutputInterface $output): bool
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
            return true;
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
        $model = $this->migrationModelFactory->createModel($this->patchRepository);
        return $model->load(['status' => ['new', 'error']], ['order']);
    }

    protected function getSeeds(): array
    {
        $model = $this->migrationModelFactory->createModel($this->seedRepository);
        return $model->load(['status' => ['new', 'error']], ['order']);
    }

    protected function getTables(): array
    {
        $model = $this->migrationModelFactory->createModel($this->tableRepository);
        return $model->load(['status' => ['new', 'error']], ['order']);
    }
}
