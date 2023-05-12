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

use DateTimeInterface;

/**
 * Step engine that can select any previous round for begin date and any round for calculating the end date.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class AnyStepEngine extends \Gems\Tracker\Engine\StepEngineAbstract
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

        $rounds = array();
        foreach ($this->_rounds as $roundId => $round) {
            if (($roundId == $itemData['gro_id_round'])) {
                continue;   // Skip self
            }
            $rounds[$roundId] = $this->getRound($roundId)->getFullDescription();
        }

        return $this->_applyOptions($model, 'gro_valid_after_id', $rounds, $itemData);
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
        $this->_ensureRounds();

        $rounds = array();
        foreach ($this->_rounds as $roundId => $round) {
            $rounds[$roundId] = $this->getRound($roundId)->getFullDescription();
        }

        if (!empty($itemData['gro_id_round'])) {
            $rounds[$itemData['gro_id_round']] = $this->_('This round');
        } else {
            // For new rounds we use 0. The snippet will update this on save
            // to the new roundid
            $rounds['0'] = $this->_('This round');
        }
        return $this->_applyOptions($model, 'gro_valid_for_id', $rounds, $itemData);
    }

    /**
     * The end date of any round can depend on any token so always do a end date check
     * of the previous tokens
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack The respondent track to check
     * @param \Gems\Tracker\Token $startToken The token to start at
     * @param int $userId Id of the user who takes the action (for logging)
     * @param \Gems\Tracker\Token $skipToken Optional token to skip in the recalculation
     * @return int The number of tokens changed by this code
     */
    public function checkTokensFrom(\Gems\Tracker\RespondentTrack $respTrack, \Gems\Tracker\Token $startToken, $userId, \Gems\Tracker\Token $skipToken = null)
    {
        $changed = parent::checkTokensFrom($respTrack, $respTrack->getFirstToken(), $userId, $skipToken);

        return $changed;
    }

    /**
     * A longer description of the workings of the engine.
     *
     * @return string Name
     */
    public function getDescription()
    {
        return $this->_('Engine for tracks where a rounds activation can depend on any previous survey.');
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->_('Previous Survey');
    }

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults()
    {
        $defaults = parent::getRoundDefaults();

        // Now check if the valid for depends on the same round
        if (count($defaults) > 1) {
            $lastRound = end($this->_rounds);   // We need the ID to compare
            if ($defaults['gro_valid_for_source']     == 'tok' &&
                    $defaults['gro_valid_for_field']  == 'gto_valid_from' &&
                    $defaults['gro_valid_for_id']     == $lastRound['gro_id_round']) {
                $defaults['gro_valid_for_id'] = 0;  // Will be updated on save
            }
        } else {
            $defaults['gro_valid_after_source'] = 'rtr';
        }

        return $defaults;
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return \MUtil\Model\ModelAbstract
     */
    public function getRoundModel($detailed, $action)
    {
        $model = parent::getRoundModel($detailed, $action);

        $model->set('gro_valid_for_id',
                'default', '0');

        return $model;
    }

    /**
     * Returns the date to use to calculate the ValidFrom if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems\Tracker\Token $token
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @return ?DateTimeInterface date time or null
     */
    protected function getValidFromDate($fieldSource, $fieldName, $prevRoundId, \Gems\Tracker\Token $token, \Gems\Tracker\RespondentTrack $respTrack)
    {
        return $this->getValidUntilDate($fieldSource, $fieldName, $prevRoundId, $token, $respTrack, false);
    }

    /**
     * Returns the date to use to calculate the ValidUntil if any
     *
     * @param string $fieldSource Source for field from round
     * @param string $fieldName Name from round
     * @param int $prevRoundId Id from round
     * @param \Gems\Tracker\Token $token
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param ?DateTimeInterface $validFrom The calculated new valid from value
     * @return ?DateTimeInterface date time or null
     */
    protected function getValidUntilDate($fieldSource, $fieldName, $prevRoundId, \Gems\Tracker\Token $token, \Gems\Tracker\RespondentTrack $respTrack, $validFrom)
    {
        $date = null;

        switch ($fieldSource) {
            case parent::ANSWER_TABLE:
                if ($prev = $respTrack->getActiveRoundToken($prevRoundId, $token)) {
                    if ($prev->isCompleted()) {
                        $date = $prev->getAnswerDateTime($fieldName);
                    }
                }
                break;

            case parent::TOKEN_TABLE:
                if ($prev = $respTrack->getActiveRoundToken($prevRoundId, $token)) {
                    if ((false !== $validFrom) && ($prev === $token) && ($fieldName == 'gto_valid_from')) {
                        $date = $validFrom;
                    } else {
                        $date = $prev->getDateTime($fieldName);
                    }
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
