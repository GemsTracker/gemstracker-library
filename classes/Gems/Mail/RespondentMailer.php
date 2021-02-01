<?php

/**
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
class Gems_Mail_RespondentMailer extends \Gems_Mail_MailerAbstract
{
    /**
     * @var int User ID of user who sent the mail
     */
    protected $by;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var integer Mail Job ID
     */
    protected $mailjob;

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

    protected function afterMail()
    {
        $this->logRespondentCommunication();
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
            $this->addMessage($this->_('Respondent data not found'));
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
            // $result['bcc']          = $this->mailFields['project_bcc'];
            $result['email']        = $this->respondent->getEmailAddress();
            $result['from']         = '';
            $result['first_name']   = $this->respondent->getFirstName();
            $result['full_name']    = $this->respondent->getFullName();
            $result['greeting']     = $this->respondent->getGreeting();
            $result['salutation']   = $this->respondent->getSalutation();
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

    /**
     * Log the communication for the respondent.
     */
    protected function logRespondentCommunication()
    {
        $currentUserId                = $this->loader->getCurrentUser()->getUserId();
        $changeDate                   = new \MUtil_Db_Expr_CurrentTimestamp();

        $logData['grco_id_to']        = $this->respondent->getId() ?: 0;

        if (! is_int($this->by)) {
            $this->by = $currentUserId;
        }

        $logData['grco_id_by']        = $this->by;
        $logData['grco_organization'] = $this->organizationId ?: 0;

        $logData['grco_method']       = 'email';
        $logData['grco_topic']        = substr($this->applyFields($this->subject), 0, 120);

        $to = array();
        foreach($this->to as $name=> $address) {
            $to[] = $name . '<'.$address.'>';
        }

        $logData['grco_address']      = substr(join(',', $to), 0, 120);
        $logData['grco_sender']       = substr($this->from, 0, 120);

        $logData['grco_id_message']   = $this->templateId ? $this->templateId : null;
        $logData['grco_id_job']       = $this->mailjob ? $this->mailjob : null;

        $logData['grco_changed']      = $changeDate;
        $logData['grco_changed_by']   = $this->by;
        $logData['grco_created']      = $changeDate;
        $logData['grco_created_by']   = $this->by;

        $this->db->insert('gems__log_respondent_communications', $logData);
    }

    /**
     * Sets the ID of the user who sent the mail
     * @param [type] $userId [description]
     */
    public function setBy($userId)
    {
        $this->by = $userId;
    }

    /**
     * Set the mail job for logging purposes
     *
     * @param integer $jobId
     */
    public function setMailjob($jobId)
    {
        $this->mailjob = $jobId;
    }
}
