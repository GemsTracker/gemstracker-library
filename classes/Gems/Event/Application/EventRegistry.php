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
        MenuAdd::NAME => MenuAdd::class,
        // Set the correct directory for the current Controller
        SetFrontControllerDirectory::NAME => SetFrontControllerDirectory::class,

        // Add translation files to the translation object
        ZendTranslateEvent::NAME => ZendTranslateEvent::class,

        // Generic model create event marker, must be implemented per model to work
        'gems.model.create.*' => ModelCreateEvent::class,

        // Get an array of available Track Field Types for the FieldMaintenanceModel
        'gems.tracker.fieldtypes.get' => TranslatableNamedArrayEvent::class,
        // Get an array of available Track Field Dependencies for the FieldMaintenanceModel
        'gems.tracker.fielddependencies.get' => NamedArrayEvent::class,

        // Change token or token answers after survey completion. (Track builder event has priority 100)
        'gems.survey.completed' => TokenEvent::class,

        // Change token or token answers before survey start. (Track builder event has priority 100)
        'gems.survey.before-answering' => TokenEvent::class,

        // Change token or token answers after round changed. (Track builder event has priority 100)
        'gems.round.changed' => TokenEvent::class,

        // Change answers displayed bridge or model when displaying answers. (Track builder event has priority 100)
        'gems.survey.answers.display-filter' => AnswerFilterEvent::class,

        // Change respondent track or fields when field gets updated (Track builder event has priority 100)
        'gems.track.field-update' => RespondentTrackEvent::class,

        // Change respondent track or fields before field gets updated (Track builder event has priority 100)
        'gems.track.before-field-update' => RespondentTrackFieldEvent::class,
    ];
}
