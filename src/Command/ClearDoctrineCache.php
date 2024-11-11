<?php

namespace Gems\Command;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'doctrine:clear-cache', description: 'Clears the doctrine metadata, query and result cache')]
class ClearDoctrineCache extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager->getConfiguration()->getMetadataCache()->clear();

        $this->entityManager->getConfiguration()->getQueryCache()->clear();

        $this->entityManager->getConfiguration()->getResultCache()->clear();

        return Command::SUCCESS;
    }
}