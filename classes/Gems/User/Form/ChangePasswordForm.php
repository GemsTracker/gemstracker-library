<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_User_Form_ChangePasswordForm extends \Gems_Form_AutoLoadFormAbstract
        implements \Gems_User_Validate_GetUserInterface
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
     * The field name for the report no rule enforcement element.
     *
     * @var string
     */
    protected $_reportNoEnforcementFieldName = 'report_no_enforcement';

    /**
     * The field name for the report rules element.
     *
     * @var string
     */
    protected $_reportRulesFieldName = 'report_rules';

    /**
     * Layout table
     *
     * @var \MUtil_Html_TableElements
     */
    protected $_table;

    /**
     * Should a user specific check question be asked?
     *
     * @var boolean
     */
    protected $askCheck = false;

    /**
     * Should the old password be requested.
     *
     * Calculated when null
     *
     * @var boolean
     */
    protected $askOld = null;

    /**
     * Returns an array of elements for check fields during password reset and/or
     * 'label name' => 'required value' pairs. vor asking extra questions before allowing
     * a password change.
     *
     * Default is asking for the username but you can e.g. ask for someones birthday.
     *
     * @return array Of 'label name' => 'required values' or \Zend_Form_Element elements
     */
    protected $checkFields = array();

    /**
     * Should the password rules be enforced.
     *
     * @var boolean
     */
    protected $forceRules = true;

    /**
     * Should the password rules be reported.
     *
     * @var boolean
     */
    protected $reportRules = true;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     *
     * @var \Gems_User_User
     */
    protected $user;

    /**
     * Use the default form table layout
     *
     * @var boolean
     */
    protected $useTableLayout = false;

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     *
     * @param mixed $links
     */
    public function addButtons($links)
    {
        if ($links && $this->_table) {
            $this->_table->tf(); // Add empty cell, no label
            $this->_table->tf($links);
        }
    }

    /**
     * Add user defined checkfields
     *
     * @return void
     */
    protected function addCheckFields()
    {
        $check = 1;
        foreach ($this->checkFields as $label => &$value) {
            if ($value instanceof \Zend_Form_Element) {
                $element = $value;
            } else {
                if ($value) {
                    $element = new \Zend_Form_Element_Text('check_' . $check);
                    $element->setAllowEmpty(false);
                    $element->setLabel($label);

                    $validator = new \Zend_Validate_Identical($value);
                    $validator->setMessage(
                            sprintf($this->_('%s is not correct.'), $label),
                            \Zend_Validate_Identical::NOT_SAME
                            );
                    $element->addValidator($validator);

                    $value = $element;
                    $check++;
                } else {
                    // Nothing to check for
                    unset($this->checkFields[$label]);
                    continue;
                }
            }
            $this->addElement($element);
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->initTranslateable();

        parent::afterRegistry();
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->user instanceof \Gems_User_User) {
            return parent::checkRegistryRequestsAnswers();
        } else {
            return false;
        }
    }

    /**
     * Should a user specific check question be asked?
     *
     * @return boolean
     */
    public function getAskCheck()
    {
        return $this->askCheck;
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
     * Returns/sets a new password element.
     *
     * @return \Zend_Form_Element_Password
     */
    public function getNewPasswordElement()
    {
        $element = $this->getElement($this->_newPasswordFieldName);

        if (! $element) {
            // Field new password
            $element = new \Zend_Form_Element_Password($this->_newPasswordFieldName);
            $element->setLabel($this->_('New password'));
            $element->setAttrib('size', 40);
            $element->setRequired(true);
            $element->setRenderPassword(true);
            $element->addValidator(new \Gems_User_Validate_NewPasswordValidator($this->user));

            $validator = new \MUtil_Validate_IsConfirmed($this->_newPasswordFieldName, $this->_('Repeat password'));
            $validator->setMessage(
                    $this->_("Must be the same as %fieldDescription%."),
                    \MUtil_Validate_IsConfirmed::NOT_SAME
                    );
            $element->addValidator($validator);

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets a check old password element.
     *
     * @return \Zend_Form_Element_Password
     */
    public function getOldPasswordElement()
    {
        $element = $this->getElement($this->_oldPasswordFieldName);

        if (! $element) {
            // Field current password
            $element = new \Zend_Form_Element_Password($this->_oldPasswordFieldName);
            $element->setLabel($this->_('Current password'));
            $element->setAttrib('size', 40);
            $element->setRenderPassword(true);
            $element->setRequired(true);
            $element->addValidator(
                    new \Gems_User_Validate_UserPasswordValidator($this->user, $this->_('Wrong password.'))
                    );

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets a repeat password element.
     *
     * @return \Zend_Form_Element_Password
     */
    public function getRepeatPasswordElement()
    {
        $element = $this->getElement($this->_repeatPasswordFieldName);

        if (! $element) {
            // Field repeat password
            $element = new \Zend_Form_Element_Password($this->_repeatPasswordFieldName);
            $element->setLabel($this->_('Repeat password'));
            $element->setAttrib('size', 40);
            $element->setRequired(true);
            $element->setRenderPassword(true);

            $validator = new \MUtil_Validate_IsConfirmed($this->_newPasswordFieldName, $this->_('New password'));
            $validator->setMessage(
                    $this->_("Must be the same as %fieldDescription%."),
                    \MUtil_Validate_IsConfirmed::NOT_SAME
                    );
            $element->addValidator($validator);

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets an element showing the password rules
     *
     * @return \MUtil_Form_Element_Html
     */
    public function getReportNoEnforcementElement()
    {
        $element = $this->getElement($this->_reportNoEnforcementFieldName);

        if (! $element) {
            // Show no enforcement info
            $element = new \MUtil_Form_Element_Html($this->_reportNoEnforcementFieldName);
            $element->setLabel($this->_('Rule enforcement'));

            $element->div()->strong($this->_('Choose a non-compliant password to force a password change at login.'));
            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets an element showing the password rules
     *
     * @return \MUtil_Form_Element_Html
     */
    public function getReportRulesElement()
    {
        $element = $this->getElement($this->_reportRulesFieldName);

        if (! $element) {
            $info = $this->user->reportPasswordWeakness();

            // Show password info
            if ($info) {
                $element = new \MUtil_Form_Element_Html($this->_reportRulesFieldName);
                $element->setLabel($this->_('Password rules'));

                if (1 == count($info)) {
                    $element->div(sprintf($this->_('A password %s.'), reset($info)));
                } else {
                    foreach ($info as &$line) {
                        $line .= ';';
                    }
                    $line[strlen($line) - 1] = '.';

                    $element->div($this->_('A password:'))->ul($info);
                }
                $this->addElement($element);
            }
        }

        return $element;
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        return $this->_('Save');
    }

    /**
     * Returns a user
     *
     * @return \Gems_User_User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     *
     * @return boolean
     */
    public function hasRuleEnforcement()
    {
        return $this->forceRules;
    }

    /**
     * Function that checks the setup of this class/traight
     *
     * This function is not needed if the variables have been defined correctly in the
     * source for this object and theose variables have been applied.
     *
     * return @void
     */
    protected function initTranslateable()
    {
        if ($this->translateAdapter instanceof \Zend_Translate_Adapter) {
            // OK
            return;
        }

        if ($this->translate instanceof \Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            return;
        }

        if ($this->translate instanceof \Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new \MUtil_Translate_Adapter_Potemkin();
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

        if ($valid === false && $this->forceRules === false) {
            $messages = $this->getMessages();
            // If we don't enforce password rules, we pass validation but leave error messages in place.
            if (count($messages) == 1 && array_key_exists('new_password', $messages)) {
                $valid = true;
            }
        }

        if ($valid) {
            $this->user->setPassword($data['new_password']);

        } else {
            if ($this ->getAskOld() && isset($data['old_password'])) {
                if ($data['old_password'] === strtoupper($data['old_password'])) {
                    $this->addError($this->_('Caps Lock seems to be on!'));
                }
            }
            $this->populate($data);
        }

        return $valid;
    }

    /**
     * The function that determines the element load order
     *
     * @return \Gems_User_Form_LoginForm (continuation pattern)
     */
    public function loadDefaultElements()
    {
        if ($this->getAskOld()) {
            $this->getOldPasswordElement();
        }
        if ($this->getAskCheck()) {
            $this->addCheckFields();
        }

        $this->getNewPasswordElement();
        $this->getRepeatPasswordElement();
        $this->getSubmitButton();

        if (! $this->forceRules) {
            $this->getReportNoEnforcementElement();
        }

        if ($this->reportRules) {
            $this->getReportRulesElement();
        }

        if ($this->useTableLayout) {
            /****************
             * Display form *
             ****************/
            $this->_table = new \MUtil_Html_TableElement(array('class' => 'formTable'));
            $this->_table->setAsFormLayout($this, true, true);
            $this->_table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
        }

        return $this;
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }

    /**
     * Should a user specific check question be asked?
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $askCheck
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setAskCheck($askCheck = true)
    {
        $this->askCheck = $askCheck;

        return $this;
    }

    /**
     * Should the form ask for an old password
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $askOld
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setAskOld($askOld = true)
    {
        $this->askOld = $askOld;

        return $this;
    }

    /**
     * Set optional user specific check question to be asked when getAskCheck() is on.
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param array $checkFields Of 'label name' => 'required values' or \Zend_Form_Element elements
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setCheckFields(array $checkFields)
    {
        $this->checkFields = $checkFields;

        return $this;
    }

    /**
     * Should the form report the password rules
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $reportRules
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setReportRules($reportRules = true)
    {
        $this->reportRules = $reportRules;

        return $this;
    }

    /**
     * Should the form enforce the password rules
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $forceRules
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setForceRules($forceRules = true)
    {
        $this->forceRules = $forceRules;

        return $this;
    }

    /**
     * The user to change the password for
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param \Gems_User_User $user
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
     */
    public function setUser(\Gems_User_User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Should the form report use the default form table layout
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $useTableLayout
     * @return \Gems_User_Form_ChangePasswordForm (continuation pattern)
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
