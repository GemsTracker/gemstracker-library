<?php

namespace Gems\Command;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Command\DebugCommand;

class DebugMessageCommandFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DebugCommand
    {
        $config = $container->get('config');
        $handlersPerBus = [];
        if (isset($config['messenger'], $config['messenger']['buses'])) {
            foreach($config['messenger']['buses'] as $busName => $busSettings) {
                if (isset($busSettings['handlers'])) {
                    $handlersPerBus[$busName] = $busSettings['handlers'];
                }
            }
        }

        return new DebugCommand($handlersPerBus);
    }
}