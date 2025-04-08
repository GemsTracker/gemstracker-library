<?php

namespace Gems\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear-config-cache', description: 'Clears the config cache')]
class ClearConfigCache extends Command
{

    protected ?string $configCacheFileLocation;
    public function __construct(array $config)
    {
        $this->configCacheFileLocation = $config['config_cache_path'] ?? null;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->configCacheFileLocation && file_exists($this->configCacheFileLocation)) {
            unlink($this->configCacheFileLocation);
            if (file_exists($this->configCacheFileLocation)) {
                $output->writeln("<comment>Config cache at '$this->configCacheFileLocation' was NOT cleared!</comment>");
                return static::FAILURE;
            }
            $output->writeln("<info>Config cache at '$this->configCacheFileLocation' has been cleared</info>");
            return static::SUCCESS;
        }
        $output->writeln("<comment>No Config cache found at '$this->configCacheFileLocation'</comment>");
        return static::FAILURE;
    }
}