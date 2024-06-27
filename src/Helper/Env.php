<?php

namespace Gems\Helper;

class Env
{
    public function __construct(
        public string $name
    )
    {}

    public static function get(string $name, string|null $default = null): string|null
    {
        return $_ENV[$name] ?? $_SERVER[$name] ?? $default;
    }

    public static function processArray(array $config): array
    {
        foreach($config as $configKey => $configValue) {
            if (is_array($configValue)) {
                $config[$configKey] = static::processArray($configValue);
            }
            if ($configValue instanceof Env) {
                $config[$configKey] = static::get($configValue->name);
            }
        }

        return $config;
    }
}