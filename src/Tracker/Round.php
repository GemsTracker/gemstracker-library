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

use Gems\Tracker;
use MUtil\Translate\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 7, 2016 11:26:57 AM
 */
class Round
{
    /**
     *
     * @var string
     */
    protected ?string $_description = null;

    /**
     *
     * @var \Gems\Tracker\Survey
     */
    protected Survey|null|false $_survey = false;

    public function __construct(
        protected readonly array $roundData,
        protected readonly Translator $translator,
        protected readonly Tracker $tracker,
    )
    {}

    /**
     * Get a round description with order number and survey name
     *
     * @return string
     */
    public function getFullDescription(): string
    {
        if ($this->_description) {
            return $this->_description;
        }

        $descr        = $this->getRoundDescription();
        $hasDescr     = ($descr !== null && strlen(trim($descr)));
        $order        = $this->getRoundOrder();
        $survey       = $this->getSurvey();
        $surveyExists = $survey ? $survey->exists : false;

        if ($order) {
            if ($hasDescr) {
                if ($surveyExists) {
                    $this->_description = sprintf('%d: %s - %s', $order, $descr, $survey->getName());
                } else {
                    $this->_description = sprintf('%d: %s', $order, $descr);
                }
            } elseif ($surveyExists) {
                $this->_description = sprintf('%d: %s', $order, $survey->getName());
            } else {
                $this->_description = $order;
            }
        } elseif ($hasDescr) {
            if ($surveyExists) {
                $this->_description = sprintf('%s - %s', $descr, $survey->getName());
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
    public function getRoundDescription(): string|null
    {
        return $this->roundData['gro_round_description'] ?? null;
    }

    /**
     * Get the round id for this round
     *
     * @return int
     */
    public function getRoundId(): int|null
    {
        return $this->roundData['gro_id_round'] ?? null;
    }

    /**
     * Get the round order for this round
     *
     * @return int
     */
    public function getRoundOrder(): int|null
    {
        return $this->roundData['gro_id_order'] ?? null;
    }

    /**
     * Get the survey id for this round
     *
     * @return \Gems\Tracker\Survey
     */
    public function getSurvey():? Survey
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
    public function getSurveyId(): int|null
    {
        return $this->roundData['gro_id_survey'] ?? null;
    }

    /**
     * Is this an active round
     *
     * @return boolean
     */
    public function isActive(): bool
    {
        return (bool) ($this->roundData['gro_active'] ?? false);
    }

}
