<?php

namespace Gems\Command;

use Gems\Cache\ApplicationCacheRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear-all-cache', description: 'Clears all cache')]
class ClearAllCache extends Command
{
    public function __construct(
        protected ApplicationCacheRepository $applicationCacheRepository,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->applicationCacheRepository->clearCache()) {
            $output->writeln('<info>Cache has been cleared</info>');
        } else {
            $output->writeln('<error>Cache could not been cleared</error>');
        }
        if ($this->applicationCacheRepository->clearConfigCache()) {
            $output->writeln('<info>Config cache has been cleared</info>');
        } else {
            $output->writeln('<error>Config cache could not be cleared</error>');
        }
        if ($this->applicationCacheRepository->clearAutoConfigCache()) {
            $output->writeln('<info>Autoconfig cache has been cleared</info>');
        } else {
            $output->writeln('<error>Autoconfig cache could not be cleared</error>');
        }
        if ($this->applicationCacheRepository->clearTranslationCache()) {
            $output->writeln('<info>Translation cache has been cleared</info>');
        } else {
            $output->writeln('<error>Translation cache could not be cleared</error>');
        }
        if ($this->applicationCacheRepository->clearDoctrineCache()) {
            $output->writeln('<info>Doctrine cache has been cleared</info>');
        } else {
            $output->writeln('<error>Doctrine cache could not be cleared</error>');
        }
        return static::FAILURE;
    }
}