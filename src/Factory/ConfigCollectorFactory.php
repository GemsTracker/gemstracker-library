<?php

namespace Gems\Factory;

use DebugBar\DataCollector\ConfigCollector;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ConfigCollectorFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        return new ConfigCollector($config, 'config');
    }
}