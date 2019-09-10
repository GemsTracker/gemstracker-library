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
class LastAnswerCondition extends RoundConditionAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Gems_Tracker
     */
    public $tracker;

    public function afterRegistry()
    {
        parent::afterRegistry();
        if ($this->loader && !$this->tracker) {
            $this->tracker = $this->loader->getTracker();
        }
    }

    protected function getComparators()
    {
        $compators = [
            \Gems\Conditions::COMPARATOR_EQUALS    => $this->_('Equals'),
            \Gems\Conditions::COMPARATOR_NOT       => $this->_('Does not equal'),
            \Gems\Conditions::COMPARATOR_EQUALLESS => $this->_('Equal or less'),
            \Gems\Conditions::COMPARATOR_EQUALMORE => $this->_('Equal or more'),
            \Gems\Conditions::COMPARATOR_BETWEEN   => $this->_('Between'),
            \Gems\Conditions::COMPARATOR_CONTAINS  => $this->_('Contains'),
            \Gems\Conditions::COMPARATOR_IN        => $this->_('In (..|..)'),
        ];
        natsort($compators);

        return $compators;
    }

    public function getHelp()
    {
        return $this->_("Look back from the current survey and find the first answered question");
    }
    
    public function getLastAnswer($questionCode, $token)
    {
        $questionCodeUc = strtoupper($questionCode);
        $answer = 'N/A';    // Default if we find no answer
        
        // We look back from this token, so we can even recalc if needed
        $prev    = $token;
        
        while ($prev = $prev->getPreviousSuccessToken()) {
            if (!$prev->getReceptionCode()->isSuccess() || !$prev->isCompleted()) {
                continue;
            }    
                
            $answers   = $prev->getRawAnswers();
            $answersUc = array_change_key_case($answers, CASE_UPPER);
                
            if (array_key_exists($questionCodeUc, $answersUc)) {
                $answer = $answersUc[$questionCodeUc];
                break;
            }
        }
        
        return $answer;
    }

    public function getModelFields($context, $new)
    {
        $comparators = $this->getComparators();

        $result = [
            'gcon_condition_text1' => ['label' => $this->_('Question code'), 'elementClass' => 'text'],
            'gcon_condition_text2' => ['label' => $this->_('Comparison operator'), 'elementClass' => 'select', 'multiOptions' => $comparators],
            'gcon_condition_text3' => ['elementClass' => 'Hidden', 'value' => ''],
            'gcon_condition_text4' => ['elementClass' => 'Hidden', 'value' => ''],
        ];

        if (!(isset($context['gcon_condition_text2']) && $context['gcon_condition_text2'] && array_key_exists($context['gcon_condition_text2'], $comparators))) {
            $context['gcon_condition_text2'] = key($comparators);
        }
        $comparator   = $this->getComparator($context['gcon_condition_text2'], []);
        $labels       = $comparator->getParamLabels();
        $descriptions = $comparator->getParamDescriptions();
        switch ($comparator->getNumParams()) {
            case 2:
                $result['gcon_condition_text4'] = ['label' => $labels[1], 'description' => $descriptions[1], 'elementClass' => 'text'];
                // intentional fall through
                
            case 1:
                $result['gcon_condition_text3'] = ['label' => $labels[0], 'description' => $descriptions[0], 'elementClass' => 'text'];

            default:
                break;
        }

        return $result;
    }

    public function getName()
    {
        return $this->_('Previous answer');
    }

    public function getNotValidReason($conditionId, $context)
    {
        return sprintf($this->_('There is no question with `%s` in this track.'), $this->_data['gcon_condition_text1']);
    }

    public function getRoundDisplay($trackId, $roundId)
    {
        $field      = $this->_data['gcon_condition_text1'];
        $comparator = $this->_data['gcon_condition_text2'];
        $param1     = $this->_data['gcon_condition_text3'];
        $param2     = $this->_data['gcon_condition_text4'];

        $fieldText  = sprintf($this->_('Last answer to question `%s`'), $field);

        $comparatorDescription = '';
        if (!empty($comparator)) {
            $comparatorDescription = $this->getComparator($comparator, [$param1, $param2])
                    ->getDescription($fieldText);
        }

        return $comparatorDescription;
    }

    public function isRoundValid(\Gems_Tracker_Token $token)
    {
        $questionCode = $this->_data['gcon_condition_text1'];
        $comparator   = $this->_data['gcon_condition_text2'];
        $param1       = $this->_data['gcon_condition_text3'];
        $param2       = $this->_data['gcon_condition_text4'];

        $answer = $this->getLastAnswer($questionCode, $token);        
        
        return $this->getComparator($comparator, [$param1, $param2])
                ->isValid($answer);
    }

    /**
     * Does this track have the fieldcode the condition depends on?
     *
     * @param type $conditionId
     * @param type $context
     * @return boolean
     */
    public function isValid($conditionId, $context)
    {
        // For now always valid, checking all surveys and possible questions could slow things down
        $result = true;

        return $result;
    }

}