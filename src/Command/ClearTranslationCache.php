<?php

namespace Gems\Command;

use Gems\Cache\ApplicationCacheRepository;
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
    public function __construct(
        protected readonly ApplicationCacheRepository $applicationCacheRepository,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->applicationCacheRepository->clearTranslationCache()) {
            $output->writeln('<info>Translation cache has been cleared</info>');
        } else {
            $output->writeln('<error>Translation cache could not be cleared</error>');
        }
        return static::FAILURE;
    }
}