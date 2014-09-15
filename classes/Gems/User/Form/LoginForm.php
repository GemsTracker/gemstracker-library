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
class Gems_User_Form_LoginForm extends Gems_User_Form_OrganizationFormAbstract
{
    /**
     * The field name for the lost password element.
     *
     * @var string
     */
    protected $_lostPasswordFieldName = 'lost_password';

    /**
     * The field name for the token element.
     *
     * @var string
     */
    protected $_tokenFieldName = 'token_link';

    /**
     * The field name for the password element.
     *
     * @var string
     */
    public $passwordFieldName = 'password';

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

    public function __construct($options = null)
    {
        if (isset($options['class'])) {
            $options['class'] .= ' col-sm-4';
        } else {
            $options['class'] = 'col-sm-4';
        }
        parent::__construct($options);
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
     * Returns/sets a password element.
     *
     * @return Zend_Form_Element_Password
     */
    public function getPasswordElement()
    {
        $element = $this->getElement($this->passwordFieldName);

        if (! $element) {
            // Veld password
            $element = $this->createElement('password', $this->passwordFieldName);
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
     * Returns the label for the submitbutton
     *
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        return $this->translate->_('Login');
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
            $element = $this->createElement('html', $this->_tokenFieldName);
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
}
