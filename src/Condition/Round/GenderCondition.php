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

use Gems\Condition\ConditionLoader;
use Gems\Condition\RoundConditionAbstract;
use Gems\Tracker;
use Gems\Tracker\Token;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;

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
    public function __construct(protected ConditionLoader $conditions,
        protected Tracker $tracker,
        TranslatorInterface $translator,
        protected Translated $translatedUtil
    )
    {
        parent::__construct($conditions, $translator);
    }

    protected function getComparators(): array
    {
        $comparators = [
            ConditionLoader::COMPARATOR_EQUALS   => $this->_('Equals'),
            ConditionLoader::COMPARATOR_NOT      => $this->_('Does not equal'),
        ];
        natsort($comparators);

        return $comparators;
    }
    
    public function getHelp(): string
    {
        return $this->_("Round will be valid depending on the gender of the respondent or the relation assinged to the survey.");
        /**
         * Choose respondent (always respondent, regardless of the filler (relation)
         *     or filler (the respondent of the relation)
         */
    }

    public function getModelFields(array $context, bool $new): array
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
        
        $genders = $this->translatedUtil->getGenders();

        return [
            'gcon_condition_text1' => ['label' => $this->_('Determine gender based on'), 'elementClass' => 'Select', 'multiOptions' => $subjects, 'value' => $subject],
            'gcon_condition_text2' => ['label' => $this->_('Comparison operator'), 'elementClass' => 'select', 'multiOptions' => $this->getComparators()],
            'gcon_condition_text3' => ['label' => $this->_('Gender'), 'elementClass' => 'select', 'multiOptions' => $genders],
            'gcon_condition_text4' => ['elementClass' => 'Hidden', 'value' => ''],
        ];
    }

    public function getName(): string
    {
        return $this->_('Respondent or relation gender');
    }

    public function getNotValidReason(int $value, array $context): string
    {
        if ($this->_data['gcon_condition_text1'] === 'f') {
            $reason = $this->_('Round is not for relations');
        } else {
            $reason = $this->_('Round is not for respondents');
        }
        
        return $reason;
    }

    public function getRoundDisplay(int $trackId, int $roundId): string
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

    public function isRoundValid(Token $token): bool
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

    public function isValid(int $value, array $context): bool
    {
        $result = false;

        if (isset($context['gro_id_survey']) && $context['gro_id_survey']) {
            $survey = $this->tracker->getSurvey($context['gro_id_survey']);
            if (!$survey->isTakenByStaff()) {
                // For patient or relation
                $relationField = $context['gro_id_relationfield'] ?? -1;
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
