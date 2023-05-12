<?php

namespace Gems\Command;

use Gems\Util\Lock\MaintenanceLock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:communication-lock', description: 'Enable or disable automatic messaging')]
class CommJobLock extends Command
{
    public function __construct(protected \Gems\Util\Lock\CommJobLock $commJobLock)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('value', InputArgument::OPTIONAL, 'set automatic messaging 1 to enable, 0 to disabled');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $value = $input->getArgument('value');
        if ($value === null) {
            if ($this->commJobLock->isLocked()) {
                $io->warning('Automatic messaging is currently turned DISABLED');
                return static::SUCCESS;
            }
            $io->info('Automatic messaging is currently ENABLED');
            return static::SUCCESS;
        }

        if ($value === '1' || strtolower($value) == 'disable') {
            if ($this->commJobLock->isLocked()) {
                $io->warning('Automatic messaging is already turned DISABLED');
                return static::SUCCESS;
            }
            $this->commJobLock->lock();
            $io->warning('Automatic messaging has been turned DISABLED');
            return static::SUCCESS;
        }
        if ($value === '0' || strtolower($value) == 'enable') {
            if (!$this->commJobLock->isLocked()) {
                $io->info('Automatic messaging is already ENABLED');
                return static::SUCCESS;
            }
            $this->commJobLock->unlock();
            $io->info('Automatic messaging has been ENABLED');
            return static::SUCCESS;
        }

        return static::FAILURE;
    }
}