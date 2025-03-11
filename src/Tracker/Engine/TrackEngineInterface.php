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

use Gems\Model\JoinModel;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker\Model\FieldDataModel;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Tracker\Model\RoundModel;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\Tracker\Model\TokenModel;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\Round;
use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\RoundChangedEventInterface;
use Gems\Tracker\TrackEvent\TrackBeforeFieldUpdateEventInterface;
use Gems\Tracker\TrackEvent\TrackCalculationEventInterface;
use Gems\Tracker\TrackEvent\TrackCompletedEventInterface;
use Gems\Tracker\TrackEvent\TrackFieldUpdateEventInterface;
use Mezzio\Session\SessionInterface;
use MUtil\Model\ModelAbstract;
use Zalt\Model\MetaModelInterface;

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
     * Integrate field loading en showing and editing
     *
     * @param MetaModelInterface $model
     * @param bool $addDependency True when editing, can be false in all other cases
     * @param string|null $respTrackIdField Optional Database column name where Respondent Track Id is set
     * @return \Gems\Tracker\Engine\TrackEngineAbstract
     */
    public function addFieldsToModel(MetaModelInterface $model, bool $addDependency = true, ?string $respTrackIdField = null): self;

    /**
     * Calculate the track info from the fields
     *
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo(array $data): string;

    /**
     * Calculate the number of active rounds in this track from the database.
     *
     * @return int The number of rounds in this track.
     */
    public function calculateRoundCount(): int;

    /**
     * Check for the existence of all tokens and create them otherwise
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param SessionInterface $session
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Task\TaskRunnerBatch|null $batch for counters
     */
    public function checkRoundsFor(RespondentTrack $respTrack, SessionInterface $session = null, int $userId, ?TaskRunnerBatch $batch = null): void;

    /**
     * Check the valid from and until dates in the track starting at a specified token
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param \Gems\Tracker\Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Tracker\Token|null $skipToken Optional token to skip in the recalculation
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(RespondentTrack $respTrack, Token $startToken, int $userId, ?Token $skipToken = null): int;

    /**
     * Check the valid from and until dates in the track
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFromStart(RespondentTrack $respTrack, int $userId): int;

    /**
     * Copy a track and all it's related data (rounds/fields etc)
     *
     * @param int $oldTrackId  The id of the track to copy
     * @return int              The id of the copied track
     */
    public function copyTrack(int $oldTrackId): int;

    /**
     * An array of snippet names for displaying the answers to a survey.
     *
     * @return array if string snippet names
     */
    public function getAnswerSnippetNames(): array;

    /**
     * A longer description of the workings of the engine.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return string Name
     */
    public function getDescription(): string;

    /**
     *
     * @return string External description of the track
     */
    public function getExternalName(): string;

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return TrackBeforeFieldUpdateEventInterface|null
     */
    public function getFieldBeforeUpdateEvent(): ?TrackBeforeFieldUpdateEventInterface;

    /**
     * Returns an array of the fields in this track key / value are id / code
     *
     * @return array fieldid => fieldcode With null when no fieldcode
     */
    public function getFieldCodes(): array;

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames(): array;

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId \Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsData(int $respTrackId): array;

    /**
     * Get the storage model for field values
     *
     * @return FieldDataModel
     */
    public function getFieldsDataStorageModel(): FieldDataModel;

    /**
     * Returns the field definition for the track enige.
     *
     * @return FieldsDefinition
     */
    public function getFieldsDefinition(): FieldsDefinition;

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param bool $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return FieldMaintenanceModel
     */
    public function getFieldsMaintenanceModel(bool $detailed = false, string $action = 'index'): FieldMaintenanceModel;

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldsOfType(string $fieldType): array;

    /**
     * Get the FieldUpdateEvent for this trackId
     *
     * @return TrackFieldUpdateEventInterface|null
     */
    public function getFieldUpdateEvent(): ?TrackFieldUpdateEventInterface;

    /**
     * Get the round id of the first round
     *
     * @return int \Gems id of first round
     */
    public function getFirstRoundId(): int;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return string Name
     */
    public function getName(): string;

    /**
     * Look up the round id for the next round
     *
     * @param int $roundId  \Gems round id
     * @return int \Gems round id
     */
    public function getNextRoundId(int $roundId): ?int;

    /**
     * @return array Of organization ids
     */
    public function getOrganizationIds(): array;

    /**
     * Look up the round id for the previous round
     *
     * @param int $roundId  \Gems round id
     * @param int $roundOrder Optional extra round order, for when the current round may have changed.
     * @return int|null \Gems round id
     */
    public function getPreviousRoundId(int $roundId, int $roundOrder = null): ?int;

    /**
     * Get all respondent relation fields
     *
     * Returns an array of field id => field name
     *
     * @return array
     */
    public function getRespondentRelationFields(): array;

    /**
     * Get the round object
     *
     * @param int $roundId  \Gems round id
     * @return \Gems\Tracker\Round
     */
    public function getRound(int $roundId): ?Round;

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of snippet names
     */
    public function getRoundAnswerSnippets(Token $token): array;

    /**
     * Return the Round Changed event name for this round
     *
     * @param int $roundId
     * @return RoundChangedEventInterface event instance or null
     */
    public function getRoundChangedEvent(int $roundId): ?RoundChangedEventInterface;

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults(): array;

    /**
     * The round descriptions for this track
     *
     * @return array roundId => string
     */
    public function getRoundDescriptions(): array;

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundEditSnippetNames(): array;

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param bool $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return RoundModel
     */
    public function getRoundModel(bool $detailed, string $action): RoundModel;

    /**
     * Get all the round objects
     *
     * @return array of roundId => \Gems\Tracker\Round
     */
    public function getRounds(): array;

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundShowSnippetNames(): array;

    /**
     * An array of snippet names for deleting a token.
     *
     * @param Token $token Allows token status dependent delete snippets
     * @return array of string snippet names
     */
    public function getTokenDeleteSnippetNames(Token $token): array;

    /**
     * An array of snippet names for editing a token.
     *
     * @param Token $token Allows token status dependent edit snippets
     * @return array of string snippet names
     */
    public function getTokenEditSnippetNames(Token $token): array;

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return StandardTokenModel|TokenModel
     */
    public function getTokenModel(): StandardTokenModel|TokenModel;

    /**
     * An array of snippet names for displaying a token
     *
     * @param Token $token Allows token status dependent show snippets
     * @return array of string snippet names
     */
    public function getTokenShowSnippetNames(Token $token): array;

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @return TrackCalculationEventInterface | null
     */
    public function getTrackCalculationEvent(): ?TrackCalculationEventInterface;

    /**
     *
     * @return string The gems track code
     */
    public function getTrackCode(): string;

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @return TrackCompletedEventInterface|null
     */
    public function getTrackCompletionEvent(): ?TrackCompletedEventInterface;

    /**
     *
     * @return int The track id
     */
    public function getTrackId(): int;

    /**
     *
     * @return string The gems track name
     */
    public function getTrackName(): string;

    /**
     * The track type of this engine
     *
     * @return string 'T' or 'S'
     */
    public function getTrackType(): string;

    /**
     * Is the field an appointment type
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isAppointmentField(string $fieldName): bool;

    /**
     * True if the user can create this kind of track in TrackMaintenanceAction.
     * False if this type of track is created by specialized user interface actions.
     *
     * Engine level function, should be the same for each class instance.
     *
     * @return boolean
     */
    public function isUserCreatable(): bool;

    /**
     * Updates the number of rounds in this track.
     *
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function updateRoundCount(int $userId): int;
}
