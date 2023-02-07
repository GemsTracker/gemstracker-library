<?php

namespace Gems\Command;

use Gems\Util\Lock\MaintenanceLock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $value = $input->getArgument('value');
        if ($value === null) {
            if ($this->maintenanceLock->isLocked()) {
                $output->writeln("<error>Maintenance mode is currently ON</error>");
                return static::SUCCESS;
            }
            $output->writeln("<info>Maintenance mode is currently OFF</info>");
            return static::SUCCESS;
        }

        if ($value === '1' || strtolower($value) == 'on') {
            if ($this->maintenanceLock->isLocked()) {
                $output->writeln("<error>Maintenance mode is already ON</error>");
                return static::SUCCESS;
            }
            $this->maintenanceLock->lock();
            $output->writeln("<error>Maintenance mode has been turned ON</error>");
            return static::SUCCESS;
        }
        if ($value === '0' || strtolower($value) == 'off') {
            if (!$this->maintenanceLock->isLocked()) {
                $output->writeln("<info>Maintenance mode is already OFF</info>");
                return static::SUCCESS;
            }
            $this->maintenanceLock->unlock();
            $output->writeln("<info>Maintenance mode has been turned OFF</info>");
            return static::SUCCESS;
        }

        return static::FAILURE;
    }
}