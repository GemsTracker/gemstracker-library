<?php

namespace Gems\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zalt\Model\Data\DataReaderInterface;

abstract class RunMigrationAbstract extends Command
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

    protected function configure()
    {
        $this->addArgument('id', InputArgument::OPTIONAL, sprintf('%s ID. ~ or all for all', ucfirst($this->topic)));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $model = $this->getModel();

        $info = $model->load(['status' => ['new', 'error']], ['order']);

        $io = new SymfonyStyle($input, $output);

        if (count($info) === 0) {
            $io->info(sprintf('No %s found', $this->topicPlural));
            return Command::SUCCESS;
        }

        $id = $input->getArgument('id');

        if ($id === null)  {
            $displayInfo = [];
            foreach($info as $index => $patch) {
                $displayInfo[] = ['index' => $index + 1] + array_intersect_key($patch, $this->displayColumns);
            }

            $table = new Table($output);
            $table->setHeaders($this->displayColumns);
            $table->setRows($displayInfo);

            $table->render();

            $id = $io->ask(sprintf('Run %s: (empty for none, ~ for all', $this->topic));
        }

        if ($id === null) {
            return Command::SUCCESS;
        }

        $id = trim($id);

        if ($id === '~' || $id === 'all') {
            return $this->runAllMigrations($info, $io);
        }

        if (is_numeric($id) && isset($info[$id-1])) {
            return $this->runMigration($info[$id-1], $io);
        }

        if (str_contains($id, '-')) {
            // It's a range!
            $idParts = array_map('trim', explode('-', $id));
            if (!is_numeric($idParts[0]) || !is_numeric($idParts[1])) {
                $io->error('Range does not consist of numbers');
                return static::FAILURE;
            }
            $items = array_slice($info, ($idParts[0] - 1), ($idParts[1] - $idParts[0] + 1));
            return $this->runAllMigrations($items, $io);
        }
        if (str_contains($id, ',')) {
            // It's a range!
            $idParts = array_map('trim', explode(',', $id));
            $items = [];
            foreach($idParts as $idPart) {
                if (!is_numeric($idPart) || !isset($info[$idPart - 1])) {
                    $io->error(sprintf('%s %s does not exist', ucfirst($this->topic), $id));
                    return static::FAILURE;
                }
                $items[] = $info[$idPart - 1];
            }
            return $this->runAllMigrations($items, $io);
        }

        foreach($info as $item) {
            if ($item['name'] === $id) {
                return $this->runMigration($item, $io);
            }
        }

        $io->error(sprintf('%s %s not found', ucfirst($this->topic), $id));
        return static::FAILURE;
    }

    abstract protected function getModel(): DataReaderInterface;

    protected function runAllMigrations(array $infoList, SymfonyStyle $io): int
    {
        foreach($infoList as $info) {
            $result = $this->runMigration($info, $io);

            if ($result === Command::FAILURE) {
                return $result;
            }
        }

        return Command::SUCCESS;
    }

    abstract protected function repositoryRunMigration(array $info): void;

    protected function runMigration(array $info, SymfonyStyle $io): int
    {
        try {
            $this->repositoryRunMigration($info);
            $io->success(sprintf('%s %s successfully executed', ucfirst($this->topic), $info['name']));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('%s %s failed.. %s', ucfirst($this->topic), $info['name'], $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
