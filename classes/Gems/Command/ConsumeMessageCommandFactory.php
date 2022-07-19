<?php

namespace Gems\Command;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\RoutableMessageBus;

class ConsumeMessageCommandFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ConsumeMessagesCommand
    {
        $routableBus = $container->get(RoutableMessageBus::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $logger = null;
        if ($container->has('messageBusLogger')) {
            $logger = $container->get('messageBusLogger');
        }

        return new ConsumeMessagesCommand($routableBus, $container, $eventDispatcher, $logger);
    }
}