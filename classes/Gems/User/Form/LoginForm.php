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
class Gems_User_Form_LoginForm extends Gems_Form_AutoLoadFormAbstract implements Gems_User_Validate_GetUserInterface
{
    /**
     * The field name for the lost password element.
     *
     * @var string
     */
    protected $_lostPasswordFieldName = 'lost_password';

    /**
     * When true the organization was derived from the the url
     *
     * @var boolean
     */
    protected $_organizationFromUrl = false;

    /**
     * The field name for the submit element.
     *
     * @var string
     */
    protected $_submitFieldName = 'button';

    /**
     * The field name for the token element.
     *
     * @var string
     */
    protected $_tokenFieldName = 'token_link';

    /**
     *
     * @var Gems_User_User
     */
    protected $_user;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * The field name for the organization element.
     *
     * @var string
     */
    public $organizationFieldName = 'organization';

    /**
     * For small numbers of organizations a multiline selectbox will be nice. This
     * setting handles how many lines will display at once. Use 1 for the normal
     * dropdown selectbox
     *
     * @var int
     */
    protected $organizationMaxLines = 6;

    /**
     * The field name for the password element.
     *
     * @var string
     */
    public $passwordFieldName = 'password';

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * The default behaviour for showing a lost password button
     *
     * @var boolean
     */
    protected $showPasswordLost = true;

    /**
     * The default behaviour for showing an 'ask token' button
     *
     * @var boolean
     */
    protected $showToken = true;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * The field name for the username element.
     *
     * @var string
     */
    public $usernameFieldName = 'userlogin';

    /**
     *
     * @var Zend_Util
     */
    protected $util;

    /**
     * Returns the organization id that should currently be used for this form.
     *
     * @return int Returns the current organization id, if any
     */
    public function getCurrentOrganizationId()
    {
        $userLoader = $this->loader->getUserLoader();

        // Url determines organization first.
        if ($orgId = $userLoader->getOrganizationIdByUrl()) {
            $this->_organizationFromUrl = true;
            $userLoader->getCurrentUser()->setCurrentOrganization($orgId);
            return $orgId;
        }

        $request = $this->getRequest();
        if ($request->isPost() && ($orgId = $request->getParam($this->organizationFieldName))) {
            return $orgId;
        }

        return $userLoader->getCurrentUser()->getCurrentOrganizationId();
    }

    /**
     *  Returns a list with the organizations the user can select for login.
     *
     * @return array orgId => Name
     */
    public function getLoginOrganizations()
    {
        return $this->util->getDbLookup()->getOrganizationsForLogin();
    }

    /**
     * Returns/sets a link to the reset password page
     *
     * @return MUtil_Form_Element_Html
     */
    public function getLostPasswordElement()
    {
        $element = $this->getElement($this->_lostPasswordFieldName);

        if (! $element) {
            // Reset password
            $element = new MUtil_Form_Element_Html($this->_lostPasswordFieldName);
            // $element->br();
            $element->setValue($this->getLostPasswordLink());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns an html link to the reset password page
     *
     * @return MUtil_Html_AElement
     */
    public function getLostPasswordLink()
    {
        return new MUtil_Html_AElement(array('controller' => 'index', 'action' => 'resetpassword'), $this->translate->_('Lost password'), array('class' => 'actionlink'));
    }

    /**
     * Returns/sets an element for determining / selecting the organization.
     *
     * @return Zend_Form_Element_Xhtml
     */
    public function getOrganizationElement()
    {
        $element = $this->getElement($this->organizationFieldName);
        $orgId   = $this->getCurrentOrganizationId();
        $orgs    = $this->getLoginOrganizations();
        $hidden  = $this->_organizationFromUrl || (count($orgs) < 2);

        if ($hidden) {
            if (! $element instanceof Zend_Form_Element_Hidden) {
                $element = new Zend_Form_Element_Hidden($this->organizationFieldName);

                $this->addElement($element);
            }

            if (! $this->_organizationFromUrl) {
                $orgIds = array_keys($orgs);
                $orgId  = reset($orgIds);
            }

        } elseif (! $element instanceof Zend_Form_Element_Select) {
            $element = new Zend_Form_Element_Select($this->organizationFieldName);
            $element->setLabel($this->translate->_('Organization'));
            $element->setRequired(true);
            $element->setMultiOptions($orgs);

            if ($this->organizationMaxLines > 1) {
                $element->setAttrib('size', max(count($orgs) + 1, $this->organizationMaxLines));
            }
            $this->addElement($element);

        }
        $element->setValue($orgId);

        return $element;
    }

    /**
     * Returns/sets a password element.
     *
     * @return Zend_Form_Element_Password
     */
    public function getPasswordElement()
    {
        $element = $this->getElement($this->passwordFieldName);

        if (! $element) {
            // Veld password
            $element = new Zend_Form_Element_Password($this->passwordFieldName);
            $element->setLabel($this->translate->_('Password'));
            $element->setAttrib('size', 40);
            $element->setRequired(true);

            if ($this->getOrganizationElement() instanceof Zend_Form_Element_Hidden) {
                $explain = $this->translate->_('Combination of user and password not found.');
            } else {
                $explain = $this->translate->_('Combination of user and password not found for this organization.');
            }
            $element->addValidator(new Gems_User_Validate_GetUserPasswordValidator($this, $explain));

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Return the Request object
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (! $this->request) {
            $this->request = Zend_Controller_Front::getInstance()->getRequest();
        }
        return $this->request;
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
            $element->setLabel(null === $label ? $this->translate->_('Login') : $label);
            $element->setAttrib('class', 'button');

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns/sets a link for the token input page.
     *
     * @return MUtil_Form_Element_Html
     */
    public function getTokenElement()
    {
        $element = $this->getElement($this->_tokenFieldName);

        if (! $element) {
            // Veld token
            $element = new MUtil_Form_Element_Html($this->_tokenFieldName);
            // $element->br();
            $element->setValue($this->getTokenLink());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns an html link for the token input page.
     *
     * @return MUtil_Html_AElement
     */
    public function getTokenLink()
    {
        return MUtil_Html::create('a', array('controller' => 'ask', 'action' => 'token'), $this->translate->_('Enter your token...'), array('class' => 'actionlink'));
    }

    /**
     * Returns a user
     *
     * @return Gems_User_User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Returns/sets a login name element.
     *
     * @return Zend_Form_Element_Text
     */
    public function getUserNameElement()
    {
        $element = $this->getElement($this->usernameFieldName);

        if (! $element) {
            // Veld inlognaam
            $element = new Zend_Form_Element_Text($this->usernameFieldName);
            $element->setLabel($this->translate->_('Username'));
            $element->setAttrib('size', 40);
            $element->setRequired(true);

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
        $this->_user = $this->loader->getUser($data[$this->usernameFieldName], $data[$this->organizationFieldName]);

        return parent::isValid($data, $disableTranslateValidators);
    }

    /**
     * The function that determines the element load order
     *
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function loadDefaultElements()
    {
        $this->getOrganizationElement();
        $this->getUserNameElement();
        $this->getPasswordElement();
        $this->getSubmitButton();

        if ($this->showPasswordLost) {
            $this->getLostPasswordElement();
        }
        if ($this->showToken) {
            $this->getTokenElement();
        }

        return $this;
    }

    /**
     * For small numbers of organizations a multiline selectbox will be nice. This
     * setting handles how many lines will display at once. Use 1 for the normal
     * dropdown selectbox
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param int $organizationMaxLines
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function setOrganizationMaxLines($organizationMaxLines)
    {
        $this->organizationMaxLines = $organizationMaxLines;

        return $this;
    }

    /**
     * The behaviour for showing a lost password button
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $showPasswordLost
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function setShowPasswordLost($showPasswordLost = true)
    {
        $this->showPasswordLost = $showPasswordLost;

        return $this;
    }

    /**
     * The default behaviour for showing an 'ask token' button
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $showToken
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function setShowToken($showToken = true)
    {
        $this->showToken = $showToken;

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
