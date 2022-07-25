<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker;

use MUtil\Ra\RaObject;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 7, 2016 11:26:57 AM
 */
class Round extends RaObject
{
    /**
     *
     * @var string
     */
    protected $_description;

    /**
     *
     * @var \Gems\Tracker\Survey
     */
    protected $_survey = false;

    /**
     *
     * @var \Gems\Tracker
     */
    protected $tracker;

    /**
     * Get a round description with order number and survey name
     *
     * @return string
     */
    public function getFullDescription()
    {
        if ($this->_description) {
            return $this->_description;
        }

        $descr        = $this->getRoundDescription();
        $hasDescr     = strlen(trim($descr));
        $order        = $this->getRoundOrder();
        $survey       = $this->getSurvey();
        $surveyExists = $survey ? $survey->exists : false;

        if ($order) {
            if ($hasDescr) {
                if ($surveyExists) {
                    $this->_description = sprintf($this->_('%d: %s - %s'), $order, $descr, $survey->getName());
                } else {
                    $this->_description = sprintf($this->_('%d: %s'), $order, $descr);
                }
            } elseif ($surveyExists) {
                $this->_description = sprintf($this->_('%d: %s'), $order, $survey->getName());
            } else {
                $this->_description = $order;
            }
        } elseif ($hasDescr) {
            if ($surveyExists) {
                $this->_description = sprintf($this->_('%s - %s'), $descr, $survey->getName());
            } else {
                $this->_description = $descr;
            }
        } else {
            if ($surveyExists) {
                $this->_description = $survey->getName();
            } else {
                $this->_description = '';
            }
        }

        return $this->_description;
    }

    /**
     * Get the round description for this round
     *
     * @return string
     */
    public function getRoundDescription()
    {
        return $this->offsetDefault('gro_round_description');
    }

    /**
     * Get the round id for this round
     *
     * @return int
     */
    public function getRoundId()
    {
        return $this->offsetDefault('gro_id_round');
    }

    /**
     * Get the round order for this round
     *
     * @return int
     */
    public function getRoundOrder()
    {
        return $this->offsetDefault('gro_id_order');
    }

    /**
     * Get the survey id for this round
     *
     * @return \Gems\Tracker\Survey
     */
    public function getSurvey()
    {
        if (false !== $this->_survey) {
            return $this->_survey;
        }
        $surveyId = $this->getSurveyId();

        if ($surveyId) {
            $this->_survey = $this->tracker->getSurvey($surveyId);
        } else {
            $this->_survey = null;
        }

        return $this->_survey;
    }

    /**
     * Get the survey id for this round
     *
     * @return int
     */
    public function getSurveyId()
    {
        return $this->offsetDefault('gro_id_survey');
    }

    /**
     * Is this an active round
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->offsetDefault('gro_active', false);
    }

}
