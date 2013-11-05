<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

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
 * @since      Class available since version 1.6.1
 */
class Gems_Tracker_Source_LimeSurvey2m00Database extends Gems_Tracker_Source_LimeSurvey1m91Database
{
    /**
     * Check a token table for any changes needed by this version.
     *
     * @param array $tokenTable
     * @return array Fieldname => change field commands
     */
    protected function _checkTokenTable(array $tokenTable)
    {
        $missingFields = parent::_checkTokenTable($tokenTable);

        return self::addnewAttributeFields($tokenTable, $missingFields);
    }

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * Adds the fields without default new in 2.00
     *
     * @param Gems_Tracker_Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(Gems_Tracker_Token $token)
    {
        $values = parent::_fillAttributeMap($token);

        return self::addnewAttributeDefaults($values);
    }

    /**
     * Adds the fields without default new in 2.00
     *
     * @param Gems_Tracker_Token $token
     * @return array Of fieldname => value type
     */
    public static function addnewAttributeDefaults(array $values)
    {
        // Not really attributes, but they need a value
        $values['participant_id'] = '';
        $values['blacklisted']    = '';

        return $values;
    }

    /**
     * Adds the fields without default new in 2.00
     *
     * @param array $tokenTable
     * @param array $missingFields
     * @return array Fieldname => change field commands
     */
    public static function addnewAttributeFields(array $tokenTable, array $missingFields)
    {
        if (! isset($tokenTable['participant_id'])) {
            $missingFields['participant_id'] = "ADD participant_id varchar(50) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL";
        }
        if (! isset($tokenTable['blacklisted'])) {
            $missingFields['blacklisted'] = "ADD blacklisted varchar(17) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL";
        }

        return $missingFields;
    }
}