<?php


namespace Gems\Event\Application;


/**
 * Central registry for available event types, code not executed
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2020 Erasmus MC & Equipe Zorgbedrijven
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class EventRegistry
{
    public static $events = [
        //Get an array with database config paths
        GetDatabasePaths::NAME => GetDatabasePaths::class,
        // Add or change menu items after creation, but before project settings
        MenuAdd::Name => MenuAdd::class,
        // Set the correct directory for the current Controller
        SetFrontControllerDirectory::NAME => SetFrontControllerDirectory::class,

        'gems.tracker.conditions.get' => TranslatableNamedArrayEvent::class,

        // Get an array of available Track Field Types for the FieldMaintenanceModel
        'gems.tracker.fieldtypes.get' => TranslatableNamedArrayEvent::class,
        // Get an array of available Track Field Dependencies for the FieldMaintenanceModel
        'gems.tracker.fielddependencies.get' => NamedArrayEvent::class,
    ];
}
