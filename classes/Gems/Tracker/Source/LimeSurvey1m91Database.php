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
 * @since      Class available since version 1.4.1
 */
class Gems_Tracker_Source_LimeSurvey1m91Database extends \Gems_Tracker_Source_LimeSurvey1m9Database
{
    /**
     * In 1.91 the field private = y was changed to anonymized = y
     *
     * @var string The LS version dependent field name for anonymized surveys
     */
    protected $_anonymizedField = 'anonymized';

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * Added the usesleft value.
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(\Gems_Tracker_Token $token)
    {
        $values = parent::_fillAttributeMap($token);

        // Not really an attribute, but it is the best place to set this
        $values['usesleft'] = $token->isCompleted() ? 0 : 1;

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

        if (! isset($tokenTable['usesleft'])) {
            $missingFields['usesleft'] = "ADD `usesleft` INT( 11 ) NULL DEFAULT '1' AFTER `completed`";
        }

        return $missingFields;
    }
}