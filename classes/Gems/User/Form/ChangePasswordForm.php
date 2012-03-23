<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: LoginForm.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_Form_ChangePasswordForm extends Gems_Form_AutoLoadFormAbstract
{
    /**
     * The field name for the new password element.
     *
     * @var string
     */
    protected $_newPasswordFieldName = 'new_password';

    /**
     * The field name for the old password element.
     *
     * @var string
     */
    protected $_oldPasswordFieldName = 'old_password';

    /**
     * The field name for the repeat password element.
     *
     * @var string
     */
    protected $_repeatPasswordFieldName = 'repeat_password';

    /**
     * The field name for the report rules element.
     *
     * @var string
     */
    protected $_reportRulesFieldName = 'report_rules';

    /**
     * The field name for the submit element.
     *
     * @var string
     */
    protected $_submitFieldName = 'submit';

    /**
     * Layout table
     *
     * @var MUtil_Html_TableElements
     */
    protected $_table;

    /**
     * Should the old password be requested.
     *
     * Calculated when null
     *
     * @var boolean
     */
    protected $askOld = null;

    /**
     * Should the password rules be reported.
     *
     * @var boolean
     */
    protected $reportRules = true;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var Gems_User_User
     */
    protected $user;

    /**
     * Use the default form table layout
     *
     * @var boolean
     */
    protected $useTableLayout = true;

    public function addButtons($links)
    {
        if ($links && $this->_table) {
            $this->_table->tf(); // Add empty cell, no label
            $this->_table->tf($links);
        }
    }

    /**
     * Should the for asking for an old password
     *
     * @return boolean
     */
    public function getAskOld()
    {
        if (null === $this->askOld) {
            // By default only ask for the old password if the user just entered
            // it but is required to change it.
            $this->askOld = (! $this->user->isPasswordResetRequired());
        }

        // Never ask for the old password if it does not exist
        //
        // A password does not always exist, e.g. when using embedded login in Pulse
        // or after creating a new user.
        return $this->askOld && $this->user->hasPassword();
    }

    /**
     * Returns/sets a mew password element.
     *
     * @return Zend_Form_Element_Password
     */
    public function getNewPasswordElement()
    {
        $element = $this->getElement($this->_newPasswordFieldName);

        if (! $element) {
            // Field new password
            $element = new Zend_Form_Element_Password($this->_newPasswordFieldName);
            $element->setLabel($this->translate->_('New password'));
            $element->setAttrib('size', 10);
            $element->setAttrib('maxlength', 20);
            $element->setRequired(true);
            $element->setRenderPassword(true);
            $element->addValidator(new Gems_User_Validate_NewPasswordValidator($this->user));
            $element->addValidator(new MUtil_Validate_IsConfirmed($this->_repeatPasswordFieldName, $this->translate->_('Repeat password')));

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets a check old password element.
     *
     * @return Zend_Form_Element_Password
     */
    public function getOldPasswordElement()
    {
        $element = $this->getElement($this->_oldPasswordFieldName);

        if (! $element) {
            // Field current password
            $element = new Zend_Form_Element_Password($this->_oldPasswordFieldName);
            $element->setLabel($this->translate->_('Current password'));
            $element->setAttrib('size', 10);
            $element->setAttrib('maxlength', 20);
            $element->setRenderPassword(true);
            $element->setRequired(true);
            $element->addValidator(new Gems_User_Validate_UserPasswordValidator($this->user, $this->translate->_('Wrong password.')));

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets a repeat password element.
     *
     * @return Zend_Form_Element_Password
     */
    public function getRepeatPasswordElement()
    {
        $element = $this->getElement($this->_repeatPasswordFieldName);

        if (! $element) {
            // Field repeat password
            $element = new Zend_Form_Element_Password($this->_repeatPasswordFieldName);
            $element->setLabel($this->translate->_('Repeat password'));
            $element->setAttrib('size', 10);
            $element->setAttrib('maxlength', 20);
            $element->setRequired(true);
            $element->setRenderPassword(true);
            $element->addValidator(new MUtil_Validate_IsConfirmed($this->_newPasswordFieldName, $this->translate->_('New password')));

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets an element showing the password rules
     *
     * @return MUtil_Form_Element_Html
     */
    public function getReportRulesElement()
    {
        $element = $this->getElement($this->_reportRulesFieldName);

        if (! $element) {
            // Show password info
            if ($info = $this->user->reportPasswordWeakness()) {
                $element = new MUtil_Form_Element_Html($this->_reportRulesFieldName);
                $element->setLabel($this->translate->_('Password rules'));

                if (1 == count($info)) {
                    $element->div(sprintf($this->translate->_('A password %s.'), reset($info)));
                } else {
                    foreach ($info as &$line) {
                        $line .= ';';
                    }
                    $line[strlen($line) - 1] = '.';

                    $element->div($this->translate->_('A password:'))->ul($info);
                }
                $this->addElement($element);
            }
        }

        return $element;
    }

    /**
     * Returns/sets a submit button.
     *
     * @param string $label
     * @return Zend_Form_Element_Submit
     */
    public function getSubmitButton($label = null)
    {
        $element = $this->getElement($this->_submitFieldName);

        if (! $element) {
            // Submit knop
            $element = new Zend_Form_Element_Submit($this->_submitFieldName);
            $element->setLabel(null === $label ? $this->translate->_('Save') : $label);
            $element->setAttrib('class', 'button');

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Validate the form
     *
     * As it is better for translation utilities to set the labels etc. translated,
     * the MUtil default is to disable translation.
     *
     * However, this also disables the translation of validation messages, which we
     * cannot set translated. The MUtil form is extended so it can make this switch.
     *
     * @param  array   $data
     * @param  boolean $disableTranslateValidators Extra switch
     * @return boolean
     */
    public function isValid($data, $disableTranslateValidators = null)
    {
        $valid = parent::isValid($data, $disableTranslateValidators);

        if ($valid) {
            $this->user->setPassword($data['new_password']);

        } else {
            if ($this ->getAskOld() && isset($data['old_password'])) {
                if ($data['old_password'] === strtoupper($data['old_password'])) {
                    $this->addError($this->translate->_('Caps Lock seems to be on!'));
                }
            }
            $this->populate($data);
        }

        return $valid;
    }

    /**
     * The function that determines the element load order
     *
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function loadDefaultElements()
    {
        if ($this->getAskOld()) {
            $this->getOldPasswordElement();
        }
        $this->getNewPasswordElement();
        $this->getRepeatPasswordElement();
        $this->getSubmitButton();

        if ($this->reportRules) {
            $this->getReportRulesElement();
        }
        if ($this->useTableLayout) {
            /****************
             * Display form *
             ****************/
            $this->_table = new MUtil_Html_TableElement(array('class' => 'formTable'));
            $this->_table->setAsFormLayout($this, true, true);
            $this->_table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
        }

        return $this;
    }

    /**
     * Should the form ask for an old password
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $askOld
     * @return Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setAskOld($askOld = true)
    {
        $this->askOld = $askOld;

        return $this;
    }

    /**
     * Should the form report the password rules
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $reportRules
     * @return Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setReportRules($reportRules = true)
    {
        $this->reportRules = $reportRules;

        return $this;
    }

    /**
     * The user to change the password for
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param Gems_User_User $user
     * @return Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setUser(Gems_User_User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Should the form report use the default form table layout
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $useTableLayout
     * @return Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setUseTableLayout($useTableLayout = true)
    {
        $this->useTableLayout = $useTableLayout;

        return $this;
    }

    /**
     * True when this form was submitted.
     *
     * @return boolean
     */
    public function wasSubmitted()
    {
        return $this->getSubmitButton()->isChecked();
    }
}
