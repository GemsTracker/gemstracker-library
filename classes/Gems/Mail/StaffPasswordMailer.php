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
 * @version    $Id: StaffPasswordMailer.php $
 * @version    $id: StaffPasswordMailer.php $
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
class Gems_Mail_StaffPasswordMailer extends Gems_Mail_StaffMailer
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

	public function afterRegistry()
    {
        parent::afterRegistry();
        $this->addMailFields($this->getResetPasswordMailFields());
    }

    /**
     * Return the mailfields for a password reset template
     * @return array
     */
    protected function getResetPasswordMailFields()
    {
    	if ($this->user->getUserId()) {
    		$result = $this->user->getResetPasswordMailFields();
    	} else {
    		$result['reset_key'] = '';
        	$result['reset_url'] = '';

    	}
    	return $result;
    }

    /**
     * Set the create account Mail template from the organization or the project
     * @return  boolean success
     */
    public function setCreateAccountTemplate()
    {
        $templateId = $this->organization->getCreateAccountTemplate();
        if ($templateId) {
            $this->setTemplate($this->organization->getCreateAccountTemplate());
            return true;
        } elseif ($this->project->getEmailCreateAccount()) {
            $this->setTemplateByCode($this->project->getEmailCreateAccount());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set the reset password Mail template from the organization or the project
     * @return  boolean success
     */
    public function setResetPasswordTemplate()
    {

        $templateId = $this->organization->getResetPasswordTemplate();

        if ($templateId) {
            $this->setTemplate($this->organization->getResetPasswordTemplate());
            return true;
        } elseif ($this->project->getEmailResetPassword()) {
            if ($this->setTemplateByCode($this->project->getEmailResetPassword())) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Use the Mail template code to select and set the template
     * @param string mail
     */
    public function setTemplateByCode($templateCode)
    {
        $select = $this->loader->getModels()->getCommTemplateModel()->getSelect();
        $select->where('gct_code = ?', $templateCode);

        $template = $this->db->fetchRow($select);
        if ($template) {
            $this->setTemplate($template['gct_id_template']);
            return true;
        } else {
            return false;
        }
    }
}