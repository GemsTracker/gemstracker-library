<?php

namespace Gems\Config\AutoConfig;

use ReflectionClass;
interface AutoConfigInterface
{
    public function __invoke(ReflectionClass $reflectionClass, array $config): array;
}