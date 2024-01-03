<?php

namespace Gems\Helper;

class Env
{
    public static function get(string $name, string|null $default = null): string|null
    {
        return $_ENV[$name] ?? $_SERVER[$name] ?? $default;
    }
}