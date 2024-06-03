<?php

namespace GemsTest\testUtils;

use Laminas\ConfigAggregator\ConfigAggregator;

trait ConfigTrait
{
    protected function getConfig(): array
    {
        $aggregator = new ConfigAggregator([
            ...$this->getModules(),
        ]);
        return $aggregator->getMergedConfig();
    }

    protected function getModules(): array
    {
        return [];
    }
}