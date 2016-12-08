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
class Gems_Mail_StaffMailer extends \Gems_Mail_MailerAbstract
{
    /**
     *
     * @var boolean True if Target data is loaded
     */
    public $dataLoaded;

    /**
     * @var \Gems_Loader
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
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var integer     Staff ID
     */
    protected $staffId;

    /**
     * @var \Gems_User_User
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

        $this->setFrom($this->user->getFrom());
        $this->addTo($this->user->getEmailAddress(), $this->user->getFullName());
        $this->setLanguage($this->user->getLocale());
    }

    /**
     * Returns true if the "email.bounce" setting exists in the project
     * configuration and is true
     * @return boolean
     */
    public function bounceCheck()
    {
        return $this->project->getStaffBounce();
    }

    public function getDataLoaded()
    {
        if ($this->user) {
            return true;
        } else {
            $this->addMessage($this->translate->_('staff data not found'));
            return false;
        }
    }

    /**
     * Set the organizationID specific to the staff class
     */
    protected function loadOrganizationId()
    {
        $this->organizationId = $this->user->getCurrentOrganizationId();
    }
}