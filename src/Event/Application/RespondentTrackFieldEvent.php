<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class RespondentTrackFieldEvent extends Event
{
    /**
     * @var array list of changed fields
     */
    protected $changed;

    /**
     * @var array list of fieldData
     */
    protected $fieldData;

    /**
     * @var \Gems\Tracker\RespondentTrack
     */
    protected $respondentTrack;

    /**
     * @var int User ID
     */
    protected $userId;

    public function __construct(\Gems\Tracker\RespondentTrack $respondentTrack, $userId, $fieldData=[])
    {
        $this->respondentTrack = $respondentTrack;
        $this->userId = $userId;
        $this->fieldData = $fieldData;
    }

    /**
     * Add values to changed values list
     *
     * @param array $changeValues
     */
    public function addChanged(array $changeValues)
    {
        if (!$this->changed) {
            $this->changed = [];
        }
        $this->changed += $changeValues;
    }

    /**
     * Get all changed values
     *
     * @return array
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @return array
     */
    public function getFieldData()
    {
        return $this->fieldData;
    }

    /**
     * @param array $data
     */
    public function setFieldData($data)
    {
        $this->fieldData = $data;
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
}
