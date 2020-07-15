<?php

/**
 *
 * @package    Gem
 * @subpackage Event\Application
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Event\Application;

use Symfony\Component\EventDispatcher\Event;

/**
 *
 * @package    Gem
 * @subpackage Event\Application
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class ModelCreateEvent extends Event
{
    const NAME_START = 'gems.model.create.';

    /**
     * @var string Current action
     */
    public $action;

    /**
     * @var boolean Detailed display or not?
     */
    public $detailed;

    /**
     * @var \MUtil_Model_ModelAbstract the model
     */
    public $model;

    /**
     * @var string the Event name for the listener
     */
    public $name;

    /**
     * ModelCreateEvent constructor.
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string                     $action
     * @param boolean                    $detailed
     */
    public function __construct(\MUtil_Model_ModelAbstract $model, $action, $detailed, $name = null)
    {
        $this->model    = $model;
        $this->action   = $action;
        $this->detailed = $detailed;
        $this->name     = self::NAME_START . ($name ? $name : $model->getName());
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return \MUtil_Model_ModelAbstract
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isDetailed()
    {
        return $this->detailed;
    }
}