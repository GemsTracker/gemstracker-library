<?php


namespace Gems\Event\Application;


use Symfony\Component\EventDispatcher\Event;

class TokenEvent extends Event
{
    /**
     * changed values list
     *
     * @var array
     */
    protected $changed;

    /**
     * @var \Gems_Tracker_Token
     */
    protected $token;

    public function __construct(\Gems_Tracker_Token $token)
    {
        $this->token = $token;
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
     * Get the event token
     *
     * @return \Gems_Tracker_Token
     */
    public function getToken()
    {
        return $this->token;
    }
}
