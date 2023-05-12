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
use Gems\Condition\ConditionLoader;
use Gems\Tracker;
use Gems\Tracker\Token;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function __construct(protected ConditionLoader $conditions, TranslatorInterface $translator, protected Tracker $tracker)
    {
        parent::__construct($conditions, $translator);
    }

    protected function getComparators()
    {
        $comparators = [
            ConditionLoader::COMPARATOR_EQUALS    => $this->_('Equals'),
            ConditionLoader::COMPARATOR_NOT       => $this->_('Does not equal'),
            ConditionLoader::COMPARATOR_EQUALLESS => $this->_('Equal or less'),
            ConditionLoader::COMPARATOR_EQUALMORE => $this->_('Equal or more'),
            ConditionLoader::COMPARATOR_BETWEEN   => $this->_('Between'),
            ConditionLoader::COMPARATOR_CONTAINS  => $this->_('Contains'),
            ConditionLoader::COMPARATOR_IN        => $this->_('In (..|..)'),
        ];
        natsort($comparators);

        return $comparators;
    }

    public function getHelp(): string
    {
        return $this->_("First pick a trackfield that has a code. Then choose your comparison operator and specify the needed parameters.");
    }

    public function getModelFields(array $context, bool $new): array
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

    public function getName(): string
    {
        return $this->_('Track field');
    }

    public function getNotValidReason(int $value, array $context): string
    {
        return sprintf($this->_('Track does not have a field with code `%s`.'), $this->_data['gcon_condition_text1']);
    }

    public function getRoundDisplay(int $trackId, int $roundId): string
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

    /**
     * @return string[]
     */
    protected function getTrackFields(): array
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

    public function isRoundValid(Token $token): bool
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
     * @param int $value
     * @param array $context
     * @return boolean
     */
    public function isValid(int $value, array $context): bool
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