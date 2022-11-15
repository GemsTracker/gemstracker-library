<?php
                
/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Condition\Track;

use Gems\Condition\Comparator\ComparatorInterface;
use Gems\Condition\ConditionLoader;
use Gems\Condition\ConditionAbstract;
use Gems\Condition\TrackConditionInterface;
use Gems\Tracker\RespondentTrack;
use DateTimeImmutable;

/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class AgeCondition extends ConditionAbstract implements TrackConditionInterface
{
    protected function getActiveComparator(): ComparatorInterface
    {
        $minAge = $this->_data['gcon_condition_text2'];
        $maxAge = $this->_data['gcon_condition_text4'];

        if ('' == trim($minAge)) {
            $comparator = $this->getComparator(ConditionLoader::COMPARATOR_EQUALLESS, [$maxAge]);
        } elseif ('' == trim($maxAge)) {
            $comparator = $this->getComparator(ConditionLoader::COMPARATOR_EQUALMORE, [$minAge]);
        } else {
            $comparator = $this->getComparator(ConditionLoader::COMPARATOR_BETWEEN, [$minAge, $maxAge]);
        }

        return $comparator;
    }

    /**
     * @inheritDoc
     */
    public function getHelp(): string
    {
        return $this->_("Track condition will be true when respondent is:\n - At least minimum age\n - But no older than maximum age");
    }

    /**
     * @inheritDoc
     */
    public function getModelFields(array $context, bool $new): array
    {
        $compareOptions = [
            'NOW' => $this->_('Now (actual current time)'),
            'TS' => $this->_('At track start data'),
            ];

        if (!array_key_exists($context['gcon_condition_text1'], $compareOptions)) {
            reset($compareOptions);
            $compVal = key($compareOptions);
        } else {
            $compVal = $context['gcon_condition_text1'];
        }

        $ageUnits = [
            'Y' => $this->_('Years'),
            'M' => $this->_('Months'),
        ];

        if (!array_key_exists($context['gcon_condition_text3'], $ageUnits)) {
            reset($ageUnits);
            $value = key($ageUnits);
        } else {
            $value = $context['gcon_condition_text3'];
        }

        return [
            'gcon_condition_text1' => ['label' => $this->_('Compare when'), 'multiOptions' => $compareOptions, 'value' => $compVal],
            'gcon_condition_text2' => ['label' => $this->_('Minimum age'), 'elementClass' => 'text', 'validators[int]' => 'Digits', 'required' => false],
            'gcon_condition_text3' => ['label' => $this->_('Age in'), 'elementClass' => 'Select', 'multiOptions' => $ageUnits, 'value' => $value],
            'gcon_condition_text4' => ['label' => $this->_('Maximum age'), 'elementClass' => 'text', 'validators[int]' => 'Digits', 'required' => false],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->_('Respondent age');
    }

    /**
     * @inheritDoc
     */
    public function getNotValidReason(int $value, array$context): string
    {
        // Never triggered
        return '';
    }

    /**
     * @inheritDoc
     */
    public function isValid(int $value, array $context): bool
    {
        // Always usable in a track
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getTrackDisplay(int $trackId): string
    {
        switch ($this->_data['gcon_condition_text1']) {
            case 'NOW':
                $start = $this->_('Age at calculation');
                break;
            case 'TS':
                $start = $this->_('Age at track start');
                break;
        }

        $comparator = $this->getActiveComparator();
        $unitHelp   = '';
        if ($this->_data['gcon_condition_text3'] == 'M') {
            $unitHelp = $this->_(' (age in months');
        }

        return $comparator->getDescription($start) . $unitHelp;
    }

    /**
     * Is the condition for this round (token) valid or not
     *
     * This is the actual implementation of the condition
     *
     * @param RespondentTrack $respTrack
     * @param array $fieldData Optional field data to use instead of data currently stored at object
     * @return bool
     */
    public function isTrackValid(RespondentTrack $respTrack, array $fieldData = null): bool
    {
        $minAge  = $this->_data['gcon_condition_text2'];
        $ageUnit = $this->_data['gcon_condition_text3'];
        $maxAge  = $this->_data['gcon_condition_text4'];

        $respondent = $respTrack->getRespondent();
        $months = false;
        if ($ageUnit == 'M') {
            $months = true;
        }
        switch ($this->_data['gcon_condition_text1']) {
            case 'NOW':
                $validFrom = new DateTimeImmutable();
                break;
            case 'TS':
            default:
                $validFrom = $respTrack->getStartDate();
                break;
        }
        $age = $respondent->getAge($validFrom, $months);
        if (! $age) {
            return false;
        }

        $comparator = $this->getActiveComparator();
        return $comparator->isValid($age);
    }
}