<?php

/**
 *
 * @package    Gem
 * @subpackage Event\Application
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */


namespace Gems\Event\Application;


use Gems\Tracker\RespondentTrack;
use Symfony\Contracts\EventDispatcher\Event;

/**
 *
 * @package    Gem
 * @subpackage Event\Application
 * @license    New BSD License
 * @since      Class available since version 1.9.0
 */
class RespondentTrackFieldUpdateEvent extends Event
{
    use NamedArrayEventTrait;

    /**
     * @var array
     */
    protected $fieldData;

    /**
     * RespondentTrackFieldUpdateEvent constructor.
     *
     * @param RespondentTrack $respondentTrack
     * @param int             $userId
     * @param array|null      $oldFieldData Optional, field data before save
     * @param array|null      $fieldData    Optional, field data after save
     */
    public function __construct(
        protected readonly RespondentTrack $respondentTrack,
        protected readonly int $userId,
        protected readonly ?array $oldFieldData = null, ?array $fieldData = null)
    {
        $this->fieldData       = $fieldData ?: $respondentTrack->getFieldData();
    }

    /**
     * @return array
     */
    public function getFieldData()
    {
        return $this->fieldData;
    }

    /**
     * @return array
     */
    public function getOldFieldData()
    {
        if (null === $this->oldFieldData) {
            return [];
        }

        return $this->oldFieldData;
    }

    /**
     * @return \Gems\Tracker\RespondentTrack
     */
    public function getRespondentTrack()
    {
        return $this->respondentTrack;
    }

    /**
     * @return int user ID
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return bool
     */
    public function hasOldFieldData()
    {
        return is_array($this->oldFieldData);
    }
}
