<?php


namespace Gems\Event\Application;


class EventRegistry
{
    public static $events = [
        //Get an array with database config paths
        GetDatabasePaths::NAME => GetDatabasePaths::class,
        // Add or change menu items after creation, but before project settings
        MenuAdd::Name => MenuAdd::class,
        // Set the correct directory for the current Controller
        SetFrontControllerDirectory::NAME => SetFrontControllerDirectory::class,
        // Get an array of available Track Field Types for the FieldMaintenanceModel
        'gems.tracker.fieldtypes.get' => TranslatableNamedArrayEvent::class,
    ];
}
