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

use Gems\Condition\Comparator\ComparatorInterface;
use Gems\Condition\ConditionLoader;
use Gems\Condition\RoundConditionAbstract;
use Gems\Tracker\Token;

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
    /**
     *
     * @return ComparatorInterface
     */
    protected function getActiveComparator(): ComparatorInterface
    {
        $minAge = $this->_data['gcon_condition_text1'];
        $maxAge = $this->_data['gcon_condition_text3'];

        if (trim($minAge) == '') {
            $comparator = $this->getComparator(ConditionLoader::COMPARATOR_EQUALLESS, [$maxAge]);
        } elseif (trim($maxAge) == '') {
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
        return $this->_("Round will be valid when respondent is:\n - At least minimum age\n - But no older than maximum age");
    }

    /**
     * @inheritDoc
     */
    public function getModelFields(array $context, bool $new): array
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
            'gcon_condition_text4' => ['label' => null, 'elementClass' => 'Hidden', 'value' => ''],
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
    public function getNotValidReason(int $value, array $context): string
    {
        // Always available
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getRoundDisplay(int $trackId, int $roundId): string
    {
        $comparator = $this->getActiveComparator();
        $unitHelp   = '';
        if ($this->_data['gcon_condition_text3'] == 'M') {
                $unitHelp = $this->_(' (age in months');
        }

        return $comparator->getDescription($this->_('Respondent age')) . $unitHelp;
    }

    /**
     * @inheritDoc
     */
    public function isRoundValid(Token $token): bool
    {
        $minAge  = $this->_data['gcon_condition_text1'];
        $maxAge  = $this->_data['gcon_condition_text3'];
        $ageUnit = $this->_data['gcon_condition_text2'];

        $validFrom = $token->getValidFrom();
        if (null === $validFrom) {
            $validFrom = new \DateTimeImmutable();
        }

        $respondent = $token->getRespondent();
        $months = false;
        if ($ageUnit == 'M') {
            $months = true;
        }
        $age = $respondent->getAge($validFrom, $months);
        $comparator = $this->getActiveComparator();
        return $comparator->isValid($age);
    }

    /**
     * @inheritDoc
     */
    public function isValid(int $value, array $context): bool
    {
        // Always available
        return true;
    }

}
