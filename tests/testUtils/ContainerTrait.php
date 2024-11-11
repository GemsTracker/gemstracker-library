<?php

namespace GemsTest\testUtils;

use Gems\Config\AutoConfigurator;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

trait ContainerTrait
{
    protected ContainerInterface $container;
    public function initContainer(): void
    {
        $config = $this->getConfig();
        $autoConfigurator = new AutoConfigurator($config);
        $autoConfig = $autoConfigurator->getAutoConfigure();
        $config = $this->getConfig($autoConfig);

        $dependencies = $config['dependencies'];
        $dependencies['services']['config'] = $config;

        $this->container = new ServiceManager($dependencies);
    }
}