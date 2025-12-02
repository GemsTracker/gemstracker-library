<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Condition\Round
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Condition\Round;

use Gems\Condition\RoundConditionAbstract;

/**
 * @package    Gems
 * @subpackage Condition\Round
 * @since      Class available since version 1.0
 */
class RepeatLessCondition extends RoundConditionAbstract
{
    /**
     * @var \Gems_Util
    */
    protected $util;

    /**
     * @inheritDoc
     */
    public function getHelp()
    {
        return $this->_("Skip this round if a previous survey was answered within a certain time");
    }

    /**
     * @inheritDoc
     */
    public function getModelFields($context, $new)
    {
        $previousOptions = [
            'tracksurvey' => $this->_('Same survey in track'),
            'survey'      => $this->_('Same survey'),
            'trackcode'   => $this->_('Same survey code in track'),
            'code'        => $this->_('Same survey code'),
        ];
        $periodOptions = $this->util->getTranslated()->getPeriodUnits();

        $output = [
            'gcon_condition_text1' => [
                'label'        => $this->_('Survey'),
                'default'      => 'tracksurvey',
                'elementClass' => 'Select',
                'multiOptions' => $previousOptions,
                ],
            'gcon_condition_text2' => [
                'label'        => $this->_('Difference unit'),
                'default'      => 'D',
                'elementClass' => 'Select',
                'multiOptions' => $periodOptions,
                ],
            'gcon_condition_text3' => [
                'label'       => $this->_('Difference units'),
                'default'      => 20,
                'elementClass' => 'Text',
                'validator'    => 'Int'
            ],
        ];

        if ((! isset($context['gcon_condition_text1'])) ||
            (! isset($previousOptions[$context['gcon_condition_text1']]))) {
            $output['gcon_condition_text1']['value'] = $output['gcon_condition_text1']['default'];
        }
        if ((! isset($context['gcon_condition_text2'])) ||
            (! isset($periodOptions[$context['gcon_condition_text2']]))) {
            $output['gcon_condition_text2']['value'] = $output['gcon_condition_text2']['default'];
        }
        if ((! isset($context['gcon_condition_text3'])) ||
            (! is_numeric($context['gcon_condition_text3']))) {
            $output['gcon_condition_text3']['value'] = $output['gcon_condition_text3']['default'];
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->_('Do not repeat survey');
    }

    /**
     * @inheritDoc
     */
    public function getNotValidReason($value, $context)
    {
        $previous = $this->_data['gcon_condition_text1'];

        switch ($previous) {
            case 'tracksurvey':
            case 'survey':
                return "is valid";

            case 'trackcode':
            case 'code':
                $tracker = $this->loader->getTracker();
                $survey = $tracker->getSurvey($context['gro_id_survey']);
                if ($survey->getCode()) {
                    return "is valid";
                }
                return sprintf(
                    $this->_('Survey %s does not have a survey code.'),
                    $survey->getName()
                );
        }
        return $this->_('Not active, invalid configuration.');
    }

    /**
     * @inheritDoc
     */
    public function getRoundDisplay($trackId, $roundId)
    {
        $previous = $this->_data['gcon_condition_text1'];
        $period   = $this->_data['gcon_condition_text2'];
        $unit     = $this->_data['gcon_condition_text3'];

        $periodOptions = $this->util->getTranslated()->getPeriodUnits();
        $periodDisplay = isset($periodOptions[$period]) ? strtolower($periodOptions[$period]) : $period;

        switch ($previous) {
            case 'tracksurvey':
                return sprintf(
                    $this->_('Previous same survey in track should be completed more than %d %s ago.'),
                    $unit,
                    $periodDisplay
                );

            case 'survey':
                return sprintf(
                    $this->_('Previous same survey for patient should be be completed more more than %d %s ago.'),
                    $unit,
                    $periodDisplay
                );

            case 'trackcode':
                return sprintf(
                    $this->_('Previous survey with same survey code in track should be completed more than %d %s ago.'),
                    $unit,
                    $periodDisplay
                );

            case 'code':
                return sprintf(
                    $this->_('Previous survey with same survey code for patient should be completed more than %d %s ago.'),
                    $unit,
                    $periodDisplay
                );
        }
        return $this->_('Not active, invalid configuration.');
    }


    /**
     * @inheritDoc
     */
    public function isRoundValid(\Gems_Tracker_Token $token)
    {
        if (! ($token->getValidFrom() && $token->getCompletionTime())) {
            return true;
        }

        $previous = $this->_data['gcon_condition_text1'];
        $period   = $this->_data['gcon_condition_text2'];
        $unit     = $this->_data['gcon_condition_text3'];
        $tracker  = $this->loader->getTracker();

        $select = $tracker->getTokenSelect(['gto_id_token']);
        $select->andReceptionCodes([], false)
            ->onlyCompleted()
            ->onlySucces()
            ->order('gto_completion_time DESC');

        switch ($previous) {
            case 'tracksurvey':
                $select->forSurveyId($token->getSurveyId())
                    ->forRespondentTrack($token->getRespondentTrackId());
                break;

            case 'survey':
                $select->forSurveyId($token->getSurveyId());
                break;

            case 'trackcode':
                if (! $token->getSurvey()->getCode()) {
                    // Always true
                    return true;
                }
                $select->forSurveyCode($token->getSurvey()->getCode())
                    ->forRespondentTrack($token->getRespondentTrackId());
                break;

            case 'code':
                if (! $token->getSurvey()->getCode()) {
                    // Always true
                    return true;
                }
                $select->forSurveyCode($token->getSurvey()->getCode());
                break;
        }
        $tokenId = $select->fetchOne();

        if (! $tokenId) {
            // Noprevious token
            return true;
        }

        $previousToken = $tracker->getToken($tokenId);
        $previousDate  = $previousToken->getCompletionTime();
        if (! $previousDate) {
            // Should not occur
            return true;
        }

        switch ($period) {
            case 'N':
                $compareTime = $previousDate->addMinute($unit);
                break;
            case 'H':
                $compareTime = $previousDate->addHour($unit);
                break;
            case 'D':
                $compareTime = $previousDate->addDay($unit);
                break;
            case 'W':
                $compareTime = $previousDate->addWeek($unit);
                break;
            case 'M':
                $compareTime = $previousDate->addMonth($unit);
                break;
            case 'Q':
                $compareTime = $previousDate->addMonth($unit * 3);
                break;
            case 'Y':
                $compareTime = $previousDate->addYear($unit);
                break;
            default:
                $compareTime = $previousDate;
                break;
        }

        return $compareTime->getTimestamp() <= $token->getCompletionTime()->getTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function isValid($value, $context)
    {
        $previous = $this->_data['gcon_condition_text1'];

        switch ($previous) {
            case 'tracksurvey':
            case 'survey':
                return true;

            case 'trackcode':
            case 'code':
                $tracker = $this->loader->getTracker();
                $survey = $tracker->getSurvey($context['gro_id_survey']);
                return (bool) $survey->getCode();
        }
        return false;
    }
}