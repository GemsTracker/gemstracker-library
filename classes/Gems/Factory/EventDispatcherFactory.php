<?php

declare(strict_types=1);

namespace Gems\Factory;

use Gems\Event\Psr11EventDispatcher;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EventDispatcher
    {
        $event = new Psr11EventDispatcher($container);

        $config = $container->get('config');
        if (isset($config['events'])) {
            if (isset($config['events']['subscribers'])) {
                foreach($config['events']['subscribers'] as $subscriberClass) {
                    if ($container->has($subscriberClass)) {
                        $subscriber = $container->get($subscriberClass);
                    } else {
                        $subscriber = new $subscriberClass;
                    }
                    $event->addSubscriber($subscriber);
                }
            }
            if (isset($config['events']['listeners'])) {
                foreach($config['events']['listeners'] as $eventName => $listeners) {
                    foreach((array)$listeners as $listener) {
                        if (is_string($listener)) {
                            $event->addListener($eventName, $listener);
                            continue;
                        }
                        // Assume array with listener callable and priority
                        $event->addListener($eventName, $listener[0], $listener[1]);
                    }
                }
            }

        }
        return $event;
    }
}
