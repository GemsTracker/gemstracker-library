<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

/**
 * Class description of LimeSurvey1m91Database
 *
 * Difference with 1.9 version:
 *   - private field was renamed to anonymized
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.1
 */
class LimeSurvey1m91Database extends \Gems\Tracker\Source\LimeSurvey1m9Database
{
    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * Added the usesleft value.
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(\Gems\Tracker\Token $token)
    {
        $values = parent::_fillAttributeMap($token);

        return $values;
    }

    /**
     * Check a token table for any changes needed by this version.
     *
     * @param array $tokenTable
     * @return array Fieldname => change field commands
     */
    protected function _checkTokenTable(array $tokenTable)
    {
        $missingFields = parent::_checkTokenTable($tokenTable);

        return $missingFields;
    }
}