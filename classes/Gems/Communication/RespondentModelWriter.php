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
 * @package    Gems
 * @subpackage Communication
 */

/**
 * Writer implementation to save respondents to the database
 * 
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @version    $Id$
 * @package    Gems
 * @subpackage Communication
 */
class Gems_Communication_RespondentModelWriter implements Gems_Communication_RespondentWriter
{
    /**
     * @var Gems_Model_RespondentModel
     */
    private $_model = null;
    
    public function __construct()
    {
        $this->_model = GemsEscort::getInstance()->getLoader()->getModels()->getRespondentModel(true);
    }
    
    /**
     * - Fetches respondent based on bsn / reception code and patient nr
     * - Creates the respondent if it does not exist, updates otherwise
     * 
	 * @see Gems_Model_RespondentModel
     * @see Gems_Communication_RespondentWriter::writeRespondent()
	 *
	 * @param  Gems_Communication_RespondentContainer $respondent
	 * @param  int $userId
	 * @return boolean True if a new respondent was added, false if one was updated 
     */
    public function writeRespondent(Gems_Communication_RespondentContainer $respondent, &$userId)
    {
        $parameters = $this->_model->applyParameters(
            array(
                'grs_ssn' => $respondent->getBsn(),
                'gr2o_reception_code' => GemsEscort::RECEPTION_OK,
                'gr2o_patient_nr' => $respondent->getPatientId()
            )
        );
        
        $data = $this->_model->loadFirst();
        $isNew = false;
        
        if (empty($data)) {
            $isNew = true;
            $data = $this->_model->loadNew();
        }
        
        unset($data['grs_email']);
        
        $data['gr2o_patient_nr'] = $respondent->getPatientId();
        $data['grs_first_name'] = $respondent->getFirstName();
        $data['grs_last_name'] = $respondent->getLastName();
        $data['grs_surname_prefix'] = $respondent->getSurnamePrefix();
        $data['grs_ssn'] = $respondent->getBsn();
        $data['grs_gender'] = $respondent->getGender();
        $data['grs_birthday'] = $respondent->getBirthday();
        
        $data = $this->_model->save($data);
        
        $userId = $data['grs_id_user'];
        
        return $isNew;
    }
}