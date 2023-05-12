<?php

namespace Gems\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear-cache', description: 'Clears the application cache')]
class ClearCache extends Command
{
    public function __construct(protected CacheItemPoolInterface $cacheItemPool)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->cacheItemPool->clear()) {
            $output->writeln('<info>Cache has been cleared</info>');
            return static::SUCCESS;
        }
        return static::FAILURE;
    }
}