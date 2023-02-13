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
        $this->addArgument('value', InputArgument::OPTIONAL, 'set automatic messaging 1 for on, 0 for off');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $value = $input->getArgument('value');
        if ($value === null) {
            if ($this->commJobLock->isLocked()) {
                $io->warning('Automatic messaging is currently ON');
                return static::SUCCESS;
            }
            $io->info('Automatic messaging is currently OFF');
            return static::SUCCESS;
        }

        if ($value === '1' || strtolower($value) == 'on') {
            if ($this->commJobLock->isLocked()) {
                $io->warning('Automatic messaging is already ON</error>');
                return static::SUCCESS;
            }
            $this->commJobLock->lock();
            $io->warning('Automatic messaging has been turned ON</error>');
            return static::SUCCESS;
        }
        if ($value === '0' || strtolower($value) == 'off') {
            if (!$this->commJobLock->isLocked()) {
                $io->info('Automatic messaging is already OFF</info>');
                return static::SUCCESS;
            }
            $this->commJobLock->unlock();
            $io->info('Automatic messaging has been turned OFF</info>');
            return static::SUCCESS;
        }

        return static::FAILURE;
    }
}