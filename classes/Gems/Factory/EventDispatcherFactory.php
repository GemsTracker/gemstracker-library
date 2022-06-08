<?php

declare(strict_types=1);

namespace Gems\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EventDispatcher
    {
        $event = new EventDispatcher();

        $config = $container->get('config');
        if (isset($config['events'])) {
            foreach($config['events'] as $subscriberClass) {
                if ($container->has($subscriberClass)) {
                    $subscriber = $container->get($subscriberClass);
                } else {
                    $subscriber = new $subscriberClass;
                }
                $event->addSubscriber($subscriber);
            }
        }
        return $event;
    }
}
