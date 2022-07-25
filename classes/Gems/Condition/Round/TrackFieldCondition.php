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
class TrackFieldCondition extends RoundConditionAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * @var \Gems\Tracker
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
        return $this->_("First pick a trackfield that has a code. Then choose your comparison operator and specify the needed parameters.");
    }

    public function getModelFields($context, $new)
    {
        $fields      = $this->getTrackFields();
        $comparators = $this->getComparators();

        $result = [
            'gcon_condition_text1' => ['label' => $this->_('Track field'), 'elementClass' => 'select', 'multiOptions' => $fields],
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
        return $this->_('Track field');
    }

    public function getNotValidReason($conditionId, $context)
    {
        return sprintf($this->_('Track does not have a field with code `%s`.'), $this->_data['gcon_condition_text1']);
    }

    public function getRoundDisplay($trackId, $roundId)
    {
        $field      = $this->_data['gcon_condition_text1'];
        $comparator = $this->_data['gcon_condition_text2'];
        $param1     = $this->_data['gcon_condition_text3'];
        $param2     = $this->_data['gcon_condition_text4'];

        $fieldText  = sprintf($this->_('Field `%s`'), $field);

        $comparatorDescription = '';
        if (!empty($comparator)) {
            $comparatorDescription = $this->getComparator($comparator, [$param1, $param2])
                    ->getDescription($fieldText);
        }

        return $comparatorDescription;
    }

    protected function getTrackFields()
    {
        // Load the track fields that have a code, and return code => name array
        $fields = $this->tracker->getAllCodeFields();
        //  We now have field ids, and codes, filter to have unqie codes
        $result = [];
        foreach($fields as $code)
        {
            $result[$code] = $code;
        }

        return $result;
    }

    public function isRoundValid(\Gems\Tracker\Token $token)
    {
        $field      = $this->_data['gcon_condition_text1'];
        $comparator = $this->_data['gcon_condition_text2'];
        $param1     = $this->_data['gcon_condition_text3'];
        $param2     = $this->_data['gcon_condition_text4'];

        $codeFields = $token->getRespondentTrack()->getCodeFields();

        // If field (no longer?) exists, return true
        if (!array_key_exists($field, $codeFields)) {
            return true;
        }

        return $this->getComparator($comparator, [$param1, $param2])
                ->isValid($codeFields[$field]);
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
        $result = false;

        if (isset($context['gro_id_track']) && $context['gro_id_track']) {
            $trackEngine = $this->tracker->getTrackEngine($context['gro_id_track']);
            $codes = $trackEngine->getFieldCodes();
            $result = in_array($this->_data['gcon_condition_text1'], $codes);
        }

        return $result;
    }

}