<?php

namespace Gems\Command;

use Gems\Config\AutoConfigurator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clear-autoconfig-cache', description: 'Clears the autoconfigurator cache')]
class ClearAutoConfiguratorCache extends Command
{

    public function __construct(
        protected readonly AutoConfigurator $autoConfigurator,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $autoconfigCachefile = $this->autoConfigurator->getAutoconfigFilename();
        if (is_null($autoconfigCachefile)) {
            $output->writeln("<comment>Autoconfig filename not configured</comment>");
            return static::FAILURE;
        }
        if ($this->autoConfigurator->clearAutoConfigConfig()) {
            $output->writeln("<info>Config cache at '$autoconfigCachefile' has been cleared</info>");
            return static::SUCCESS;
        } else {
            $output->writeln("<comment>Config cache at '$autoconfigCachefile' was NOT cleared!</comment>");
            return static::FAILURE;
        }
    }
}
