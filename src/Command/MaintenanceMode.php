<?php

namespace Gems\Command;

use Gems\Util\Lock\MaintenanceLock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:maintenance', description: 'Enable or disable maintenance mode')]
class MaintenanceMode extends Command
{
    public function __construct(protected MaintenanceLock $maintenanceLock)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('value', InputArgument::OPTIONAL, 'set Maintenance mode 1 for on, 0 for off');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $value = $input->getArgument('value');
        if ($value === null) {
            if ($this->maintenanceLock->isLocked()) {
                $io->warning('Maintenance mode is currently ON');
                return static::SUCCESS;
            }
            $io->info('Maintenance mode is currently OFF');
            return static::SUCCESS;
        }

        if ($value === '1' || strtolower($value) == 'on') {
            if ($this->maintenanceLock->isLocked()) {
                $io->warning('Maintenance mode is already ON');
                return static::SUCCESS;
            }
            $this->maintenanceLock->lock();
            $io->warning('Maintenance mode has been turned ON');
            return static::SUCCESS;
        }
        if ($value === '0' || strtolower($value) == 'off') {
            if (!$this->maintenanceLock->isLocked()) {
                $io->info('Maintenance mode is already OFF');
                return static::SUCCESS;
            }
            $this->maintenanceLock->unlock();
            $io->info('Maintenance mode has been turned OFF');
            return static::SUCCESS;
        }

        return static::FAILURE;
    }
}