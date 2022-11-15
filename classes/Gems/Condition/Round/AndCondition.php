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

use Gems\Condition\RoundConditionAbstract;
use Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class AndCondition extends RoundConditionAbstract
{
    /**
     * Load the conditions
     *
     * @return \Gems\Condition\RoundConditionInterface[]
     */
    protected function getConditions(): array
    {
        $conditionLoader = $this->conditions;
        $conditions = [];
        for ($number = 1; $number <= 4; $number++) {
            $element = 'gcon_condition_text' . $number;
            $conditionId = (int) $this->_data[$element];
            if ($conditionId >0) {
                $conditions[] = $conditionLoader->loadCondition($conditionId);
            }
        }

        return $conditions;
    }

    public function getHelp(): string
    {
        return $this->_("Combine 2 or more conditions using the AND operator. All conditions must be valid and true.");
    }

    public function getModelFields(array $context, bool $new): array
    {
        $conditions = $this->conditions->getConditionsFor(\Gems\ConditionLoader::ROUND_CONDITION);
        $messages   = [
            'gcon_id' => $this->_('The condition can not reference itself.'),
            $this->_('Conditions may be chosen only once.')
        ];        
                
        $result = [
            'gcon_condition_text1' => ['label' => $this->_('Condition'), 'elementClass' => 'select', 'multiOptions' => $conditions, 'validator'    => new \MUtil_Validate_NotEqualTo(['gcon_id'], $messages)],
            'gcon_condition_text2' => ['label' => $this->_('Condition'), 'elementClass' => 'select', 'multiOptions' => $conditions, 'validator'    => new \MUtil_Validate_NotEqualTo(['gcon_id', 'gcon_condition_text1'], $messages)],
            'gcon_condition_text3' => ['label' => $this->_('Condition'), 'elementClass' => 'select', 'multiOptions' => $conditions, 'validator'    => new \MUtil_Validate_NotEqualTo(['gcon_id', 'gcon_condition_text1', 'gcon_condition_text2'], $messages)],
            'gcon_condition_text4' => ['label' => $this->_('Condition'), 'elementClass' => 'select', 'multiOptions' => $conditions, 'validator'    => new \MUtil_Validate_NotEqualTo(['gcon_id', 'gcon_condition_text1', 'gcon_condition_text2', 'gcon_condition_text3'], $messages)]

        ];

        return $result;
    }

    public function getName(): string
    {
        return $this->_('Multiple conditions AND');
    }

    public function getNotValidReason(int $conditionId, array $context): string
    {
        $conditions = $this->getConditions();
        $text = [];
        foreach($conditions as $condition)
        {
            if (!$condition->isValid($conditionId, $context)) {
                $text[] = $condition->getNotValidReason($conditionId, $context);
            }
        }

        return join("\n", $text);
    }

    public function getRoundDisplay(int $trackId, int $roundId): string
    {
        $conditions = $this->getConditions();
        $text = [];
        foreach($conditions as $condition)
        {
            $text[] = $condition->getRoundDisplay($trackId, $roundId);
        }
        
        return join($this->_(' AND '), $text);
    }

    public function isRoundValid(Token $token): bool
    {
        $conditions = $this->getConditions();
        $valid = true;
        foreach($conditions as $condition)
        {
            $valid = $valid && $condition->isRoundValid($token);
        }
        
        return $valid;
    }

    /**
     * Does this track have the fieldcode the condition depends on?
     *
     * @param int $conditionId
     * @param int $context
     * @return bool
     */
    public function isValid(int $conditionId, array $context): bool
    {
        $conditions = $this->getConditions();
        $valid = true;
        foreach($conditions as $condition)
        {
            $valid = $valid && $condition->isValid($conditionId, $context);
        }
        
        return $valid;
    }

}