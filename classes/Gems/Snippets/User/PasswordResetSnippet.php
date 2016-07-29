<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: PasswordResetSnippet.php 2431 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\User;

use Gems\Snippets\FormSnippetAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 16:58:43
 */
class PasswordResetSnippet extends FormSnippetAbstract
{
    /**
     * Should a user specific check question be asked?
     *
     * @var boolean Not set when null
     */
    protected $askCheck = null;

    /**
     * Should the old password be requested.
     *
     * @var boolean Not set when null
     */
    protected $askOld = null;

    /**
     * Returns an array of elements for check fields during password reset and/or
     * 'label name' => 'required value' pairs. vor asking extra questions before allowing
     * a password change.
     *
     * Default is asking for the username but you can e.g. ask for someones birthday.
     *
     * @return array Of 'label name' => 'required values' or \Zend_Form_Element elements Not set when null
     */
    protected $checkFields = null;

    /**
     * Should the password rules be enforced.
     *
     * @var boolean Not set when null
     */
    protected $forceRules = null;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Should the password rules be reported.
     *
     * @var boolean Not set when null
     */
    protected $reportRules = null;

    /**
     *
     * @var \Gems_User_User
     */
    protected $user;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        if ($form instanceof \Gems_User_Form_ChangePasswordForm) {
            $form->loadDefaultElements();
        }
    }

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addSaveButton()
    {
        $this->_saveButton = $this->_form->getSubmitButton();

        parent::addSaveButton();
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $optionVars = array('askCheck', 'askOld', 'checkFields', 'forceRules', 'reportRules');
        foreach ($optionVars as $name) {
            if ($this->$name !== null) {
                $options[$name] = $this->$name;
            }
        }
        $options['useTableLayout'] = false; // Layout set by this snippet

        return $this->user->getChangePasswordForm($options);
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        if ($this->user->isPasswordResetRequired()) {
            $this->menu->setVisible(false);
            return null;
        }

        return parent::getMenuList();
    }

    /**
     * The message to display when the change is not allowed
     *
     * @return string
     */
    protected function getNotAllowedMessage()
    {
        return $this->_('You are not allowed to change your password.');
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->_('Change password');
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! ($this->user->hasAllowedRole() && $this->user->canSetPassword())) {
            $this->addMessage($this->getNotAllowedMessage());
            return false;
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        // If form is valid, but contains messages, do show them. Most likely these are the not enforced password rules
        if ($this->_form->getMessages()) {
            $this->addMessage($this->_form->getMessages());
        }
        $this->addMessage($this->_('New password is active.'));

        // If the password is valid (for the form) then it is saved by the form itself.
        return 1;
    }
}
