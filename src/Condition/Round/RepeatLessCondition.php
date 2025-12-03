<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Condition\Round
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Condition\Round;

use Gems\Condition\ConditionLoader;
use Gems\Condition\RoundConditionAbstract;
use Gems\Tracker;
use Gems\Tracker\Token;
use Gems\Util\Translated;
use Laminas\Validator\Digits;
use Zalt\Base\TranslatorInterface;

/**
 * @package    Gems
 * @subpackage Condition\Round
 * @since      Class available since version 1.0
 */
class RepeatLessCondition extends RoundConditionAbstract
{
    public function __construct(
        ConditionLoader $conditions,
        TranslatorInterface $translator,
        protected readonly Tracker $tracker,
        protected readonly Translated $translatedUtil,
    )
    {
        parent::__construct($conditions, $translator);
    }

    /**
     * @inheritDoc
     */
    public function getHelp(): string
    {
        return $this->_("Skip this round if a previous survey was answered within a certain time");
    }

    /**
     * @inheritDoc
     */
    public function getModelFields($context, $new): array
    {
        $previousOptions = [
            'tracksurvey' => $this->_('Same survey in track'),
            'survey'      => $this->_('Same survey'),
            'trackcode'   => $this->_('Same survey code in track'),
            'code'        => $this->_('Same survey code'),
        ];
        $periodOptions = $this->translatedUtil->getPeriodUnits();

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
                'validator'    => Digits::class,
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
    public function getName(): string
    {
        return $this->_('Do not repeat survey');
    }

    /**
     * @inheritDoc
     */
    public function getNotValidReason($value, $context): string
    {
        $previous = $this->_data['gcon_condition_text1'];

        switch ($previous) {
            case 'tracksurvey':
            case 'survey':
                return "is valid";

            case 'trackcode':
            case 'code':
                $survey = $this->tracker->getSurvey($context['gro_id_survey']);
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
    public function getRoundDisplay($trackId, $roundId): string
    {
        $previous = $this->_data['gcon_condition_text1'];
        $period   = $this->_data['gcon_condition_text2'];
        $unit     = $this->_data['gcon_condition_text3'];

        $periodOptions = $this->translatedUtil->getPeriodUnits();
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
    public function isRoundValid(Token $token): bool
    {
        if (! ($token->getValidFrom())) {
            return true;
        }

        $previous = $this->_data['gcon_condition_text1'];
        $period   = $this->_data['gcon_condition_text2'];
        $unit     = $this->_data['gcon_condition_text3'];

        $select = $this->tracker->getTokenSelect(['gto_id_token']);
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
            // No previous token
            return true;
        }

        $previousToken = $this->tracker->getToken($tokenId);
        $previousDate  = $previousToken->getCompletionTime();
        if (! ($previousDate instanceof \DateTimeImmutable || $previousDate instanceof \DateTime)) {
            // Should not occur
            return true;
        }

        switch ($period) {
            case 'N':
                $add = new \DateInterval('PT' . $unit . 'M');
                break;
            case 'H':
                $add = new \DateInterval('PT' . $unit . 'H');
                break;
            case 'D':
                $add = new \DateInterval('P' . $unit . 'D');
                break;
            case 'W':
                $add = new \DateInterval('P' . $unit . 'W');
                break;
            case 'M':
                $add = new \DateInterval('P' . $unit . 'M');
                break;
            case 'Q':
                $add = new \DateInterval('P' . ($unit * 3) . 'M');
                break;
            case 'Y':
                $add = new \DateInterval('P' . $unit . 'Y');
                break;

            default:
                $add = new \DateInterval('P' . $unit . 'D');
        }
        $compareTime = $previousDate->add($add);

        return $compareTime->getTimestamp() <= $token->getValidFrom()->getTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function isValid($value, $context): bool
    {
        $previous = $this->_data['gcon_condition_text1'];

        switch ($previous) {
            case 'tracksurvey':
            case 'survey':
                return true;

            case 'trackcode':
            case 'code':
                $survey = $this->tracker->getSurvey($context['gro_id_survey']);
                return (bool) $survey->getCode();
        }
        return false;
    }
}