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
class Gems_Mail_RespondentMailer extends \Gems_Mail_MailerAbstract
{
    /**
     * @var \Gems_Loader
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
     * @var \Gems_Tracker_Respondent
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
        $result['reset_ask']      = '';
        if ($this->mailFields['organization_login_url']) {
            $result['reset_ask']      = $this->mailFields['organization_login_url'] . '/index/resetpassword';
        }
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