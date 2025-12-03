<?php

namespace Gems\Log;

use Gems\Config\AutoConfig\AutoConfigInterface;
use ReflectionClass;

class AutoConfigAttributeLogger implements AutoConfigInterface
{
    public function __invoke(ReflectionClass $reflectionClass, array $config): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    $reflectionClass->getName() => AttributeLoggerFactory::class,
                ],
            ],
        ];
    }
}