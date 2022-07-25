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

/**
 * Step engine that uses a begin date from the previous round and calculates the end date using the token itself.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class NextStepEngine extends \Gems\Tracker\Engine\StepEngineAbstract
{
    /**
     * Set the surveys to be listed as valid after choices for this item and the way they are displayed (if at all)
     *
     * @param \MUtil\Model\ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applySurveyListValidAfter(\MUtil\Model\ModelAbstract $model, array &$itemData)
    {
        $this->_ensureRounds();

        $previous = $this->getPreviousRoundId($itemData['gro_id_round'], $itemData['gro_id_order']);

        if ($previous) {
            $itemData['gro_valid_after_id'] = $previous;
            $rounds[$previous] = $this->getRound($previous)->getFullDescription();

        } else {
            $itemData['gro_valid_after_id'] = null;
            $rounds = $this->util->getTranslated()->getEmptyDropdownArray();
        }
        $model->set('gro_valid_after_id', 'multiOptions', $rounds, 'elementClass', 'Exhibitor');

        return false;
    }

    /**
     * Set the surveys to be listed as valid for choices for this item and the way they are displayed (if at all)
     *
     * @param \MUtil\Model\ModelAbstract $model The round model
     * @param array $itemData    The current items data
     * @param boolean True if the update changed values (usually by changed selection lists).
     */
    protected function applySurveyListValidFor(\MUtil\Model\ModelAbstract $model, array &$itemData)
    {
        if (! (isset($itemData['gro_id_round']) && $itemData['gro_id_round'])) {
            $itemData['gro_id_round'] = '';
        }
        // Fixed value
        $itemData['gro_valid_for_id'] = $itemData['gro_id_round'];

        // The options array
        $rounds[$itemData['gro_id_round']] = $this->_('This round');

        $model->set('gro_valid_for_id', 'multiOptions', $rounds, 'elementClass', 'Exhibitor');

        return false;
    }

    /**
     * Convert a TrackEngine instance to a TrackEngine of another type.
     *
     * @see getConversionTargets()
     *
     * @param type $conversionTargetClass
     */
    public function convertTo($conversionTargetClass)
    {
        if ($conversionTargetClass == 'AnyStepEngine') {
            //TODO: Check for right sequence of rounds
        } else {
            parent::convertTo($conversionTargetClass);
        }
    }

    /**
     * Returns a list of classnames this track engine can be converted into.
     *
     * Should always contain at least the class itself.
     *
     * @see convertTo()
     *
     * @param array $options The track engine class options available in as a "track engine class names" => "descriptions" array
     * @return array Filter or adaptation of $options
     */
    public function getConversionTargets(array $options)
    {
        $results = array();

        foreach ($options as $className => $label) {
            switch ($className) {
                case 'AnyStepEngine':
                case 'NextStepEngine':
                    $results[$className] = $label;
                    break;
            }
        }
        return $results;
    }

    /**
     * A longer description of the workings of the engine.
     *
     * @return string Name
     */
    public function getDescription()
    {
        return $this->_('Engine for tracks where the next round is always dependent on the previous step.');
    }


    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->_('Next Step');
    }

    /**
     * Returns the date to use to calculate the ValidFrom if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems\Tracker\Token $token
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @return \MUtil\Date date time or null
     */
    protected function getValidFromDate($fieldSource, $fieldName, $prevRoundId, \Gems\Tracker\Token $token, \Gems\Tracker\RespondentTrack $respTrack)
    {
        $date = null;

        switch ($fieldSource) {
            case parent::TOKEN_TABLE:
                if ($prev = $token->getPreviousSuccessToken()) {
                    $date = $prev->getDateTime($fieldName);
                }
                break;

            case parent::ANSWER_TABLE:
                if ($prev = $token->getPreviousSuccessToken()) {
                    $date = $prev->getAnswerDateTime($fieldName);
                }
                break;

            case parent::APPOINTMENT_TABLE:
            case parent::RESPONDENT_TRACK_TABLE:
                $date = $respTrack->getDate($fieldName);
                break;

            case parent::RESPONDENT_TABLE:
                $date = $respTrack->getRespondent()->getDate($fieldName);
                break;
        }

        return $date;
    }

    /**
     * Returns the date to use to calculate the ValidUntil if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems\Tracker\Token $token
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param \MUtil\Date $validFrom The calculated new valid from value or null
     * @return \MUtil\Date date time or null
     */
    protected function getValidUntilDate($fieldSource, $fieldName, $prevRoundId, \Gems\Tracker\Token $token, \Gems\Tracker\RespondentTrack $respTrack, $validFrom)
    {
        $date = null;

        switch ($fieldSource) {
            case parent::NO_TABLE:
                break;

            case parent::TOKEN_TABLE:
                // Always uses the current token
                if ($fieldName == 'gto_valid_from') {
                    // May be changed but is not yet stored
                    $date = $validFrom;
                } else {
                    // No previous here, date is always from this tokens date
                    $date = $token->getDateTime($fieldName);
                }
                break;

            case parent::ANSWER_TABLE:
                if ($prev = $token->getPreviousSuccessToken()) {
                    $date = $prev->getAnswerDateTime($fieldName);
                }
                break;

            case parent::APPOINTMENT_TABLE:
            case parent::RESPONDENT_TRACK_TABLE:
                $date = $respTrack->getDate($fieldName);
                break;

            case parent::RESPONDENT_TABLE:
                $date = $respTrack->getRespondent()->getDate($fieldName);
                break;
        }

        return $date;
    }
}