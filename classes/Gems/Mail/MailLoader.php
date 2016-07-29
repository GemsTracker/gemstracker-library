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
class Gems_Mail_MailLoader extends \Gems_Loader_LoaderAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Mail';

    /**
     *
     * @var array Define the mail target options
     */
    protected $mailTargets = array(
        'staff' => 'Staff',
        'respondent' => 'Respondent',
        'token' => 'Token',
        'staffPassword' => 'Password reset',
    );

    /**
     * Return the mail elements helper class
     * @return \Gems_Mail_MailElements
     */
    public function getMailElements()
    {
        return $this->_loadClass('MailElements');
    }

    /**
     * Get the correct mailer class from the given target
     * @param  [type] $target      mailtarget (lowercase)
     * @param  array  $identifiers the identifiers needed for the specific mailtargets
     * @return \Gems_Mail_MailerAbstract class
     */
    public function getMailer($target = null, $id = false, $orgId = false)
    {

        if(isset($this->mailTargets[$target])) {
            $target = ucfirst($target);
            return $this->_loadClass($target.'Mailer', true, array($id, $orgId));
        } else {
            return false;
        }
    }

    /**
     * Get the possible mailtargets
     * @return Array  mailtargets
     */
    public function getMailTargets()
    {
        return $this->mailTargets;
    }

    /**
     * Get default mailform
     * @return \Gems_Mail_MailForm
     */
    public function getMailForm()
    {
        return $this->_loadClass('MailForm');
    }

}