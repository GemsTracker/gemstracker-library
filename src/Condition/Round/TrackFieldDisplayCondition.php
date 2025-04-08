<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2025 Equipe Zorgbedrijven B.V.
 * @license    New BSD License
 */

namespace Gems\Condition\Round;

use Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2025 Equipe Zorgbedrijven B.V.
 * @license    New BSD License
 * @since      Class available since version 2.0.36
 */
class TrackFieldDisplayCondition extends TrackFieldCondition
{
    public function getHelp(): string
    {
        return $this->_("Round will be valid if the display value of the selected track field matches the configured criteria.");
    }

    public function getName(): string
    {
        return $this->_('Track field display value');
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

        $trackField = $token->getTrackEngine()->getFieldsDefinition()->getFieldByCode($field);
        if (is_null($trackField)) {
            return true;
        }

        $displayValue = $trackField->calculateFieldInfo($codeFields[$field], $token->getRespondentTrack()->getFieldData());

        return $this->getComparator($comparator, [$param1, $param2])
                ->isValid($displayValue);
    }
}
