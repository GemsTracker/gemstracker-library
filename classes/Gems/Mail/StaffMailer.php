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
class Gems_Mail_staffMailer extends Gems_Mail_MailerAbstract
{
    /**
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Collection of the different available mailfields
     * @var array
     */
    protected $mailFields;

    /**
     * @var integer     Organization ID
     */
    protected $organizationId;

    /**
     * 
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * 
     * @var integer     Staff ID
     */
    protected $staffId;

    /**
     * @var Gems_User_User
     */
    protected $user;


    /**
     * Set Initialization variables
     */
    public function __construct($staffId=false)
    {
        $this->staffId = $staffId;
    }


	public function afterRegistry()
    {    
        $this->user = $this->loader->getUserLoader()->getUserByStaffId($this->staffId);
    	

        parent::afterRegistry();
    	
        $this->user = $this->loader->getUserLoader()->getUserByStaffId($this->staffId);
        $mailFields = $this->user->getMailFields();
        $this->addMailFields($mailFields);
        
        $this->addTo($this->user->getEmailAddress());
    }

    /**
     * Set the organizationID specific to the staff class
     */
    protected function loadOrganizationId()
    {
        $this->organizationId = $this->user->getCurrentOrganizationId();
    }
}