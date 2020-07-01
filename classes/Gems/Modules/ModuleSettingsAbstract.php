<?php


namespace Gems\Modules;

abstract class ModuleSettingsAbstract
{
    public static $addLoaderDir = true;

    public static $moduleName;

    public static $eventSubscriber;

    /**
     * @return string
     */
    protected static function getCurrentDir()
    {
        $reflection = new \ReflectionClass(static::class);
        return dirname($reflection->getFileName());
    }

    public static function getEventSubscriber()
    {
        if (static::$eventSubscriber && class_exists(static::$eventSubscriber)) {
            return static::$eventSubscriber;
        }
        return null;
    }

    public static function getLoaderDir()
    {
        if (static::$addLoaderDir) {
            return static::getCurrentDir();
        }
        return null;
    }

    public static function getVendorPath()
    {
        return dirname(static::getCurrentDir());
    }
}
