<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Condition\Round;

use Gems\Conditions;
use Gems\Condition\RoundConditionAbstract;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class GenderCondition extends RoundConditionAbstract
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
        $comparators = [
            \Gems\Conditions::COMPARATOR_EQUALS   => $this->_('Equals'),
            \Gems\Conditions::COMPARATOR_NOT      => $this->_('Does not equal'),
        ];
        natsort($comparators);

        return $comparators;
    }
    
    public function getHelp()
    {
        return $this->_("Round will be valid depending on the gender of the respondent or the relation assinged to the survey.");
        /**
         * Choose respondent (always respondent, regardless of the filler (relation)
         *     or filler (the respondent of the relation)
         */
    }

    public function getModelFields($context, $new)
    {        
        $subjects = [
            'r' => $this->_('Respondent'),
            'f' => $this->_('Relation')
        ];
        
        if (!array_key_exists($context['gcon_condition_text1'], $subjects)) {
            reset($subjects);
            $subject = key($subjects);
        } else {
            $subject = $context['gcon_condition_text1'];
        }
        
        $genders = $this->loader->getUtil()->getTranslated()->getGenders();

        return [
            'gcon_condition_text1' => ['label' => $this->_('Determine gender based on'), 'elementClass' => 'Select', 'multiOptions' => $subjects, 'value' => $subject],
            'gcon_condition_text2' => ['label' => $this->_('Comparison operator'), 'elementClass' => 'select', 'multiOptions' => $this->getComparators()],
            'gcon_condition_text3' => ['label' => $this->_('Gender'), 'elementClass' => 'select', 'multiOptions' => $genders],
            'gcon_condition_text4' => ['elementClass' => 'Hidden'],
        ];
    }

    public function getName()
    {
        return $this->_('Respondent or relation gender');
    }

    public function getNotValidReason($conditionId, $context)
    {
        if ($this->_data['gcon_condition_text1'] === 'f') {
            $reason = $this->_('Round is not for relations');
        } else {
            $reason = $this->_('Round is not for respondents');
        }
        
        return $reason;
    }

    public function getRoundDisplay($trackId, $roundId)
    {
        $subjects = [
            'r' => $this->_('Respondent'),
            'f' => $this->_('Relation')
        ];        
        
        $subject    = $this->_data['gcon_condition_text1'];
        $comparator = $this->_data['gcon_condition_text2'];
        $gender     = $this->_data['gcon_condition_text3'];
        
        $subjectLabel = array_key_exists($subject, $subjects) ? $subjects[$subject] : 'error';

        $subjectText  = sprintf($this->_('Gender of %s'), $subjectLabel);

        $comparatorDescription = '';
        if (!empty($comparator)) {
            $comparatorDescription = $this->getComparator($comparator, [$gender])
                    ->getDescription($subjectText);
        }

        return $comparatorDescription;
    }

    public function isRoundValid(\Gems_Tracker_Token $token)
    {
        $subject    = $this->_data['gcon_condition_text1'];
        $comparator = $this->_data['gcon_condition_text2'];
        $gender     = $this->_data['gcon_condition_text3'];

        if ($subject == 'r') {
            $actualGender = $token->getRespondent()->getGender();
        } else {
            $actualGender = $token->getRelation()->getGender();
        }
        
        $comparator = $this->getComparator($comparator, [$gender]);
        
        return $comparator->isValid($actualGender);        
    }

    public function isValid($conditionId, $context)
    {
        $result = false;

        if (isset($context['gro_id_survey']) && $context['gro_id_survey']) {
            $survey = $this->tracker->getSurvey($context['gro_id_survey']);
            if (!$survey->isTakenByStaff()) {
                // For patient or relation
                $relationField = isset($context['gro_id_relationfield']) ? $context['gro_id_relationfield'] : -1;
                if ($this->_data['gcon_condition_text1'] === 'r' && $relationField <= 0) {
                    return true;
                }
                
                if ($this->_data['gcon_condition_text1'] === 'f' && $relationField > 0) {
                    return true;
                }
            }
        }

        return $result;
    }

}
