<?php

namespace GemsTest\testUtils;

use Gems\Config\AutoConfigurator;
use Psr\Container\ContainerInterface;

trait ContainerTrait
{
    protected ContainerInterface $container;
    public function initContainer(): void
    {
        $config = $this->getConfig();
        $autoConfigurator = new AutoConfigurator($config);
        $config = $autoConfigurator->autoConfigure();

        $dependencies = $config['dependencies'];
        $dependencies['services']['config'] = $config;

        $this->container = new ServiceManager($dependencies);
    }
}