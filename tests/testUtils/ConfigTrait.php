<?php

namespace GemsTest\testUtils;

use GemsTest\TestConfigProvider;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;

trait ConfigTrait
{
    protected function getConfig(array $autoConfig = []): array
    {
        $aggregator = new ConfigAggregator([
            \Mezzio\Helper\ConfigProvider::class,
            \Mezzio\ConfigProvider::class,
            \Mezzio\Router\ConfigProvider::class,
            \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
            \Mezzio\Twig\ConfigProvider::class,
            \Laminas\Diactoros\ConfigProvider::class,
            new ArrayProvider($autoConfig),
            ...$this->getModules(),
            new ArrayProvider(['modules' => $this->getModules()]),
            TestConfigProvider::class,
        ]);
        return $aggregator->getMergedConfig();
    }

    protected function getModules(): array
    {
        return [];
    }
}