<?php

namespace Gems\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear-cache', description: 'Clears the application cache')]
class ClearConfigCache extends Command
{
    protected static $defaultName = 'app:clear-cache';

    private CacheItemPoolInterface $cacheItemPool;

    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->cacheItemPool->clear()) {
            return static::SUCCESS;
        }
        return static::FAILURE;
    }
}