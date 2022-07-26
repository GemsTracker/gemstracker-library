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

use Gems\Condition\RoundConditionInterface;
use Gems\Conditions;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class OrCondition extends AndCondition
{
    public function getHelp()
    {
        return $this->_("Combine 2 or more conditions using the OR operator. All conditions must be valid and at least one must be true.");
    }

    public function getName()
    {
        return $this->_('Multiple conditions OR');
    }

    public function getRoundDisplay($trackId, $roundId)
    {
        $conditions = $this->getConditions();
        $text = [];
        foreach($conditions as $condition)
        {
            $text[] = $condition->getRoundDisplay($trackId, $roundId);
        }
        
        return join($this->_(' OR '), $text);
    }

    public function isRoundValid(\Gems\Tracker\Token $token)
    {
        $conditions = $this->getConditions();
        $valid = false;
        foreach($conditions as $condition)
        {
            $valid = $valid || $condition->isRoundValid($token);
        }
        
        return $valid;
    }
}