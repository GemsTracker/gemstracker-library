<?php

namespace GemsTest\testUtils;

use Gems\ConfigProvider;
use Gems\LegacyConfigProvider;

trait ConfigModulesTrait
{
    protected function getModules(): array
    {
        return [
            ConfigProvider::class,
            LegacyConfigProvider::class,
        ];
    }
}