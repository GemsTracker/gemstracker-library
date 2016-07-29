<?php

/**
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Mail_StaffPasswordMailer extends \Gems_Mail_StaffMailer
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