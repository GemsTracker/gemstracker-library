<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition\Round;

use Gems\Conditions;
use Gems\Condition\RoundConditionAbstract;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class AgeCondition extends RoundConditionAbstract
{
    public function getHelp()
    {
        return $this->_("Round will be valid when respondent is:\n - At least minimum age\n - But no older than maximum age");
    }

    /**
     *
     * @return \Gems\Condition\Comparator\ComparatorInterface
     */
    protected function getActiveComparator()
    {
        $minAge = $this->_data['gcon_condition_text1'];
        $maxAge = $this->_data['gcon_condition_text3'];

        if (trim($minAge) == '') {
            $comparator = $this->getComparator(Conditions::COMPARATOR_EQUALLESS, [$maxAge]);
        } elseif (trim($maxAge) == '') {
            $comparator = $this->getComparator(Conditions::COMPARATOR_EQUALMORE, [$minAge]);
        } else {
            $comparator = $this->getComparator(Conditions::COMPARATOR_BETWEEN, [$minAge, $maxAge]);
        }

        return $comparator;
    }

    public function getModelFields($context, $new)
    {
        $ageUnits = [
            'Y' => $this->_('Years'),
            'M' => $this->_('Months'),
                ];

        if (!array_key_exists($context['gcon_condition_text2'], $ageUnits)) {
            reset($ageUnits);
            $value = key($ageUnits);
        } else {
            $value = $context['gcon_condition_text2'];
        }

        return [
            'gcon_condition_text1' => ['label' => $this->_('Minimum age'), 'elementClass' => 'text'],
            'gcon_condition_text2' => ['label' => $this->_('Age in'), 'elementClass' => 'Select', 'multiOptions' => $ageUnits, 'value' => $value],
            'gcon_condition_text3' => ['label' => $this->_('Maximum age'), 'elementClass' => 'text'],
            'gcon_condition_text4' => ['label' => null, 'elementClass' => 'Hidden'],
        ];
    }

    public function getName()
    {
        return $this->_('Respondent age');
    }

    public function getNotValidReason($conditionId, $context)
    {
        // Always available
        return '';
    }

    public function getRoundDisplay($trackId, $roundId)
    {
        $comparator = $this->getActiveComparator();
        $unitHelp   = '';
        if ($this->_data['gcon_condition_text3'] == 'M') {
                $unitHelp = $this->_(' (age in months');
        }

        return $comparator->getDescription($this->_('Respondent age')) . $unitHelp;
    }

    public function isRoundValid(\Gems_Tracker_Token $token)
    {
        $minAge  = $this->_data['gcon_condition_text1'];
        $maxAge  = $this->_data['gcon_condition_text3'];
        $ageUnit = $this->_data['gcon_condition_text2'];

        $validFrom = $token->getValidFrom();
        if (!is_null($validFrom)) {
            $respondent = $token->getRespondent();
            $months = false;
            if ($ageUnit == 'M') {
                $months = true;
            }
            $age = $respondent->getAge($validFrom, $months);
            $comparator = $this->getActiveComparator();
            return $comparator->isValid($age);
        }

        return true;
    }

    public function isValid($conditionId, $context)
    {
        // Always available
        return true;
    }

}
