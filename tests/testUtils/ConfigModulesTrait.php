<?php

namespace GemsTest\testUtils;

use Gems\ConfigProvider;

trait ConfigModulesTrait
{
    protected function getModules(): array
    {
        return [
            ConfigProvider::class,
        ];
    }
}