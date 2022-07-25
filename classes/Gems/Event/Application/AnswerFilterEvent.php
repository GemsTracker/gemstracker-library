<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class AnswerFilterEvent extends Event
{
    /**
     * @var \MUtil\Model\Bridge\TableBridge
     */
    protected $bridge;

    /**
     * @var array Current names
     */
    protected $currentNames;

    /**
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * AnswerFilterEvent constructor.
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $currentNames
     */
    public function __construct(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model, array $currentNames)
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
