<?php

namespace GemsTest\testUtils;

class TraitUtil
{
    public static function getClassTraits(object|string $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class) ?: []) + [$class => $class] as $class) {
            $results += static::classUsesRecursive($class);
        }

        return array_unique($results);
    }

    public static function classUsesRecursive(object|string $class): array
    {
        $traits = class_uses($class) ?: [];

        foreach ($traits as $trait) {
            $traits += static::classUsesRecursive($trait);
        }

        return $traits;
    }
}