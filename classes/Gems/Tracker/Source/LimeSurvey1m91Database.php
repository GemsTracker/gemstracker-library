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
class Gems_Tracker_Source_LimeSurvey1m91Database extends Gems_Tracker_Source_LimeSurvey1m9Database
{
    /**
     * In 1.91 the field private = y was changed to anonymized = y
     *
     * @var string The LS version dependent field name for anonymized surveys
     */
    protected $_anonymizedField = 'anonymized';

    /**
     * Sets the answers passed on.
     *
     * With the 'usesleft' feature in 1.91 we should decrease the usesleft with 1 when we insert answers
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param $answers array Field => Value array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setRawTokenAnswers(Gems_Tracker_Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb          = $this->getSourceDatabase();
        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenTable  = $this->_getTokenTableName($sourceSurveyId);
        $lsTokenId     = $this->_getToken($token->getTokenId());

        $answers = $this->_getFieldMap($sourceSurveyId)->mapTitlesToKeys($answers);

        if ($lsDb->fetchOne("SELECT token FROM $lsSurveyTable WHERE token = ?", $lsTokenId)) {
            $where = $lsDb->quoteInto("token = ?", $lsTokenId);
            $lsDb->update($lsSurveyTable, $answers, $where);
        } else {
            //No existing record, so we should insert a new row and update the 'usesleft'
            //in the token table
            $sql = $lsDb->select()
                        ->from($lsTokenTable, array('usesleft'))
                        ->where('token = ?', $lsTokenId);

            $usesLeft = $lsDb->fetchOne($sql);

            if ($usesLeft > 0) {
                $usesLeft--;
                $where = $lsDb->quoteInto("token = ?", $lsTokenId);
                $lsDb->update($lsTokenTable, array('usesleft' => $usesLeft), $where);
            } else {
                //This is an error condition, should not occur
                throw new Gems_Exception('Not allowed to use this token');
            }

            $current = new Zend_Db_Expr('CURRENT_TIMESTAMP');

            $answers['token'] = $lsTokenId;
            $answers['startlanguage'] = $this->locale->getLanguage();
            $answers['datestamp'] = $current;
            $answers['startdate'] = $current;

            $lsDb->insert($lsSurveyTable, $answers);
        }
    }
}