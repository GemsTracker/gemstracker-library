<?php

/**
 * 
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Engine;

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
interface TrackEngineInterface
{
    /**
     *
     * @param array $trackData array containing track record
     */
    public function __construct($trackData);

    /**
     * Integrate field loading en showing and editing
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param boolean $addDependency True when editing, can be false in all other cases
     * @param string $respTrackId Optional Database column name where Respondent Track Id is set
     * @return \Gems\Tracker\Engine\TrackEngineAbstract
     */
    public function addFieldsToModel(\MUtil\Model\ModelAbstract $model, $addDependency = true, $respTrackId = false);

    /**
     * Set menu parameters from this track engine
     *
     * @param \Gems\Menu\ParameterSource $source
     * @return \Gems\Tracker\Engine\TrackEngineInterface (continuation pattern)
     */
    public function applyToMenuSource(\Gems\Menu\ParameterSource $source);

    /**
     * Calculate the track info from the fields
     *
     * @param int $respTrackId \Gems respondent track id or null when new
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo(array $data);

    /**
     * Calculate the number of active rounds in this track from the database.
     *
     * @return int The number of rounds in this track.
     */
    public function calculateRoundCount();

    /**
     * Check for the existence of all tokens and create them otherwise
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Task\TaskRunnerBatch $changes batch for counters
     */
    public function checkRoundsFor(\Gems\Tracker\RespondentTrack $respTrack, $userId, \Gems\Task\TaskRunnerBatch $batch = null);

    /**
     * Check the valid from and until dates in the track starting at a specified token
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param \Gems\Tracker\Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Tracker\Token $skipToken Optional token to skip in the recalculation
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(\Gems\Tracker\RespondentTrack $respTrack, \Gems\Tracker\Token $startToken, $userId, \Gems\Tracker\Token $skipToken = null);

    /**
     * Check the valid from and until dates in the track
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return \Gems\Tracker\ChangeTracker detailed info on changes
     */
    public function checkTokensFromStart(\Gems\Tracker\RespondentTrack $respTrack, $userId);

    /**
     * Convert a TrackEngine instance to a TrackEngine of another type.
     *
     * @see getConversionTargets()
     *
     * @param type $conversionTargetClass
     */
    public function convertTo($conversionTargetClass);

    /**
     * Copy a track and all it's related data (rounds/fields etc)
     *
     * @param inte $oldTrackId  The id of the track to copy
     * @return int              The id of the copied track
     */
    public function copyTrack($oldTrackId);

    /**
     * An array of snippet names for displaying the answers to a survey.
     *
     * @return array if string snippet names
     */
    public function getAnswerSnippetNames();

    /**
     * Returns a list of classnames this track engine can be converted into.
     *
     * Should always contain at least the class itself.
     *
     * @see convertTo()
     *
     * @param array $options The track engine class options available in as a "track engine class names" => "descriptions" array
     * @return array Filter or adaptation of $options
     */
    public function getConversionTargets(array $options);

    /**
     * A longer description of the workings of the engine.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return string Name
     */
    public function getDescription();

    /**
     *
     * @return string External description of the track
     */
    public function getExternalName();

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return \Gems\Event\TrackBeforeFieldUpdateEventInterface | null
     */
    public function getFieldBeforeUpdateEvent();

    /**
     * Returns an array of the fields in this track key / value are id / code
     *
     * @return array fieldid => fieldcode With null when no fieldcode
     */
    public function getFieldCodes();

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames();

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId \Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsData($respTrackId);

    /**
     * Get the storage model for field values
     *
     * @return \Gems\Tracker\Model\FieldDataModel
     */
    public function getFieldsDataStorageModel();

    /**
     * Returns the field definition for the track enige.
     *
     * @return \Gems\Tracker\Engine\FieldsDefinition;
     */
    public function getFieldsDefinition();

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \Gems\Tracker\Model\FieldMaintenanceModel
     */
    public function getFieldsMaintenanceModel($detailed = false, $action = 'index');

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldsOfType($fieldType);

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return \Gems\Event\TrackFieldUpdateEventInterface | null
     */
    public function getFieldUpdateEvent();

    /**
     * Get the round id of the first round
     *
     * @return int \Gems id of first round
     */
    public function getFirstRoundId();

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return string Name
     */
    public function getName();

    /**
     * Look up the round id for the next round
     *
     * @param int $roundId  \Gems round id
     * @return int \Gems round id
     */
    public function getNextRoundId($roundId);

    /**
     * Look up the round id for the previous round
     *
     * @param int $roundId  \Gems round id
     * @param int $roundOrder Optional extra round order, for when the current round may have changed.
     * @return int \Gems round id
     */
    public function getPreviousRoundId($roundId, $roundOrder = null);

    /**
     * Get all respondent relation fields
     *
     * Returns an array of field id => field name
     *
     * @return array
     */
    public function getRespondentRelationFields();

    /**
     * Get the round object
     *
     * @param int $roundId  \Gems round id
     * @return \Gems\Tracker\Round
     */
    public function getRound($roundId);

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of snippet names
     */
    public function getRoundAnswerSnippets(\Gems\Tracker\Token $token);

    /**
     * Return the Round Changed event name for this round
     *
     * @param int $roundId
     * @return \Gems\Event\RoundChangedEventInterface event instance or null
     */
    public function getRoundChangedEvent($roundId);

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults();

    /**
     * The round descriptions for this track
     *
     * @return array roundId => string
     */
    public function getRoundDescriptions();

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundEditSnippetNames();

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \Gems\Model\JoinModel
     */
    public function getRoundModel($detailed, $action);

    /**
     * Get all the round objects
     *
     * @return array of roundId => \Gems\Tracker\Round
     */
    public function getRounds();

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundShowSnippetNames();

    /**
     * An array of snippet names for deleting a token.
     *
     * @param \Gems\Tracker\Token $token Allows token status dependent delete snippets
     * @return array of string snippet names
     */
    public function getTokenDeleteSnippetNames(\Gems\Tracker\Token $token);

    /**
     * An array of snippet names for editing a token.
     *
     * @param \Gems\Tracker\Token $token Allows token status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTokenEditSnippetNames(\Gems\Tracker\Token $token);

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return \Gems\Tracker\Model\StandardTokenModel
     */
    public function getTokenModel();

    /**
     * An array of snippet names for displaying a token
     *
     * @param \Gems\Tracker\Token $token Allows token status dependent show snippets
     * @return array of string snippet names
     */
    public function getTokenShowSnippetNames(\Gems\Tracker\Token $token);

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @return \Gems\Event\TrackCalculationEventInterface | null
     */
    public function getTrackCalculationEvent();

    /**
     *
     * @return string The gems track code
     */
    public function getTrackCode();

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @return \Gems\Event\TrackCompletedEventInterface|null
     */
    public function getTrackCompletionEvent();

    /**
     *
     * @return int The track id
     */
    public function getTrackId();

    /**
     *
     * @return string The gems track name
     */
    public function getTrackName();

    /**
     * The track type of this engine
     *
     * @return string 'T' or 'S'
     */
    public function getTrackType();

    /**
     * Is the field an appointment type
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isAppointmentField($fieldName);

    /**
     * True if the user can create this kind of track in TrackMaintenanceAction.
     * False if this type of track is created by specialized user interface actions.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return boolean
     */
    public function isUserCreatable();

    /**
     * Updates the number of rounds in this track.
     *
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function updateRoundCount($userId);
}
