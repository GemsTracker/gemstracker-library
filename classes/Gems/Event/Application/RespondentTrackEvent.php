<?php


namespace Gems\Event\Application;


use Symfony\Component\EventDispatcher\Event;

class RespondentTrackEvent extends Event
{
    use NamedArrayEventTrait;

    /**
     * @var \Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     * @var int User ID
     */
    protected $userId;

    public function __construct(\Gems_Tracker_RespondentTrack $respondentTrack, $userId, $fieldData=null)
    {
        $this->respondentTrack = $respondentTrack;
        $this->userId = $userId;
        $this->fieldData = $fieldData;
    }

    /**
     * @return \Gems_Tracker_RespondentTrack
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
