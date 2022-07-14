<?php

namespace Gems\OAuth2\Command;

use Defuse\Crypto\Key;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'key:generate', description: 'Generates an application key')]
class GenerateApplicationKey extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = Key::createNewRandomKey();
        return $key->saveToAsciiSafeString();
    }
}