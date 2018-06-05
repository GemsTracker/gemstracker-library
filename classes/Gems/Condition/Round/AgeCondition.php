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
    
    public function getModelFields($context, $new)
    {
        return [
            'text1' => ['label' => $this->_('Minimum age'), 'elementClass' => 'text'],
            'text2' => ['label' => null, 'elementClass' => 'Hidden'],
            'text3' => ['label' => $this->_('Maximum age'), 'elementClass' => 'text'],
            'text4' => ['label' => null, 'elementClass' => 'Hidden'],
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
        $minAge = $this->_data['gcon_condition_text1'];
        $maxAge = $this->_data['gcon_condition_text3'];
        
        return sprintf(
                $this->_('Respondent age between %s and %s'),
                $minAge,
                $maxAge
                );
    }
    
    public function isRoundValid(\Gems_Tracker_Token $token)
    {
        $validFrom = $token->getValidFrom();
        if (!is_null($validFrom)) {
            $respondent = $token->getRespondent();
            $age = $respondent->getAge($token->getValidFrom());
            return ($age >= $minAge && $age <= $maxAge);
        }
        
        return true;
    }

    public function isValid($conditionId, $context)
    {
        // Always available
        return true;
    }   

}
