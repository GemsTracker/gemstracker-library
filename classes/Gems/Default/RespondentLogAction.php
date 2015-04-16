<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentLogAction.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class Gems_Default_RespondentLogAction extends \Gems_Default_LogAction
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array('extraFilter' => 'getRespondentFilter');

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getRespondentFilter()
    {
        return array(
            'gla_respondent_id' => $this->getRespondentId(),
            'gla_organization'  => $this->_getParam(\MUtil_Model::REQUEST_ID2),
            );
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        static $respondentId = false;

        if (false !== $respondentId) {
            return $respondentId;
        }

        $orgId        = $this->_getParam(\MUtil_Model::REQUEST_ID2);
        $patientNr    = $this->_getParam(\MUtil_Model::REQUEST_ID1);

        if ($orgId && $patientNr) {
            $respondentId = $this->util->getDbLookup()->getRespondentId($patientNr, $orgId);
        } else {
            $respondentId = null;
        }

        return $respondentId;
    }
}
