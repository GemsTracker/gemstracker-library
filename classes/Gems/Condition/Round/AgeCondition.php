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
        return $this->_($text);
        
    }
    
    public function getModelFields()
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

    public function getNotValidReason($trackId, $roundId)
    {
        // Always available
        return '';
    }

    public function getRoundDisplay($trackId, $roundId)
    {
        $minAge = $this->_data['gcon_filter_text1'];
        $maxAge = $this->_data['gcon_filter_text3'];
        
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

    public function isValid($trackId, $roundId)
    {
        // Always available
        return true;
    }

    

}
