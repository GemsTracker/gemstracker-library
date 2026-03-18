<?php

namespace Gems\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:clear-translation-cache', description: 'Clears the translation cache')]
class ClearTranslationCache extends Command
{
    protected ?string $cacheLocation;
    public function __construct(array $config)
    {
        $this->cacheLocation = $config['translations']['cacheDir'] ?? null;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->cacheLocation && file_exists($this->cacheLocation)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->cacheLocation);
            if (file_exists($this->cacheLocation)) {
                $output->writeln("<comment>Translation cache at '$this->cacheLocation' was NOT cleared!</comment>");
                return static::FAILURE;
            }
            $output->writeln("<info>Translation cache at '$this->cacheLocation' has been cleared</info>");
            return static::SUCCESS;
        }
        $output->writeln("<comment>No Translation cache found at '$this->cacheLocation'</comment>");
        return static::FAILURE;
    }
}