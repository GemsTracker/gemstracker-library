<?php

/**
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Mail;

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class StaffPasswordMailer extends \Gems\Mail\StaffMailer
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
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
        } elseif ($this->config['email']['createAccountTemplate']) {
            $this->setTemplateByCode($this->config['email']['createAccountTemplate']);
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
        } elseif ($this->config['email']['resetPasswordTemplate']) {
            if ($this->setTemplateByCode($this->config['email']['resetPasswordTemplate'])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }    
}