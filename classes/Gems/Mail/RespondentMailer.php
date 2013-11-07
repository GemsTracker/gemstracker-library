<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $id RespondentMailer.php
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Mail_RespondentMailer extends Gems_Mail_MailerAbstract
{
    /**
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * @var integer     Organization ID
     */
    protected $organizationId;
    
    /**
     * @var integer     Patient ID
     */
    protected $patientId;

    /**
     * @var Gems_Tracker_Respondent
     */
    protected $respondent;



    public function __construct($patientId=false, $organizationId=false)
    {
        $this->patientId = $patientId;
        $this->organizationId = $organizationId;
    }

	public function afterRegistry()
    {
        if (!$this->respondent) {
            $this->respondent = $this->loader->getRespondent($this->patientId, $this->organizationId);
        }

        parent::afterRegistry();

        if ($this->respondent) {
            $this->addTo($this->respondent->getEmailAddress(), $this->respondent->getName());
            $this->setLanguage($this->respondent->getLanguage());
        }
    }

    public function getDataLoaded()
    {
        if ($this->respondent) {
            return true;
        } else {
            $this->addMessage($this->translate->_('Respondent data not found'));
            return false;
        }
    }

    /**
     * Get the respondent mailfields
     * @return array
     */
    protected function getRespondentMailfields()
    {
        if ($this->respondent) {
            $result = array();
            $result['bcc']          = $this->mailFields['project_bcc'];
            $result['email']        = $this->respondent->getEmailAddress();
            $result['from']         = '';
            $result['first_name']   = $this->respondent->getFirstName();
            $result['full_name']    = $this->respondent->getFullName();
            $result['greeting']     = $this->respondent->getGreeting();
            $result['last_name']    = $this->respondent->getLastName();
            $result['name']         = $this->respondent->getName();
        } else {
            $result = array(
                'email'     => '',
                'from'      => '',
                'first_name'=> '',
                'full_name' => '',
                'greeting'  => '',
                'last_name' => '',
                'name'      => ''
            );
        }

        $result['reply_to']       = $result['from'];
        $result['to']             = $result['email'];

        return $result;
    }

    /**
     * Get the respondent mailfields and add them
     */
    protected function loadMailFields()
    {
        parent::loadMailFields();
        $this->addMailFields($this->getRespondentMailFields());
    }


}