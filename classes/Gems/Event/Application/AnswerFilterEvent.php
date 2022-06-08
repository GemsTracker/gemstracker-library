<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class AnswerFilterEvent extends Event
{
    /**
     * @var \MUtil_Model_Bridge_TableBridge
     */
    protected $bridge;

    /**
     * @var array Current names
     */
    protected $currentNames;

    /**
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * AnswerFilterEvent constructor.
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $currentNames
     */
    public function __construct(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model, array $currentNames)
    {
        $this->bridge = $bridge;
        $this->model = $model;
        $this->currentNames = $currentNames;
    }

    public function getBridge()
    {
        return $this->bridge;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getCurrentNames()
    {
        return $this->currentNames;
    }

    public function setCurrentNames($currentNames)
    {
        $this->currentNames = $currentNames;
    }
}
