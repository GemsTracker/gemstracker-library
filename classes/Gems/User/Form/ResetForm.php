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
 * @version    $id: ResetForm.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class Gems_User_Form_ResetForm extends Gems_User_Form_OrganizationFormAbstract
{
    /**
     * The field name for the login link element
     *
     * @var string
     */
    protected $_loginLinkFieldName = 'loginlink';

    /**
     * The field name for the reset key element.
     *
     * @var string
     */
    protected $_resetKeyFieldName = 'key';

    /**
     * First the password reset is requested (= false), then the reset key is passed (= true)
     *
     * @var boolean Calculated when null
     */
    protected $hasResetKey = null;

    /**
     * Returns an html link for the login page.
     *
     * @return MUtil_Html_AElement
     */
    public function getLoginLink()
    {
        return MUtil_Html::create('a', array('controller' => 'index', 'action' => 'login'), $this->translate->_('Back to login'), array('class' => 'actionlink'));
    }

    /**
     * Returns a link to the login page
     *
     * @return MUtil_Form_Element_Html
     */
    public function getLoginLinkElement()
    {
        $element = $this->getElement($this->_tokenFieldName);

        if (! $element) {
            // Login link
            if ($link = $this->getLoginLink()) {
                $element = new MUtil_Form_Element_Html($this->_loginLinkFieldName);
                // $element->br();
                $element->setValue($link);

                $this->addElement($element);
            }

            return $element;
        }
    }

    /**
     * Returns an element for keeping a reset key.
     *
     * @return Zend_Form_Element_Hidden
     */
    public function getResetKeyElement()
    {
        $element = $this->getElement($this->_resetKeyFieldName);

        if (! $element) {
            $element = new Zend_Form_Element_Hidden($this->_resetKeyFieldName);

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
        if ($this->hasResetKey()) {
            return $this->translate->_('Reset password');
        } else {
            return $this->translate->_('Request password');
        }
    }

    /**
     * Returns/sets a login name element.
     *
     * @return Zend_Form_Element_Text
     * /
    public function getUserNameElement()
    {
        $element = $this->getElement($this->usernameFieldName);

        if (! $element) {
            $element = parent::getUserNameElement();

            $element->addValidator(new Gems_User_Validate_ResetKeyValidator($this, $this->translate, $this->_resetKeyFieldName));
        }

        return $element;
    }

    /**
     * Is the form working in reset mode or not
     *
     * @return boolean
     */
    public function hasResetKey()
    {
        if (null === $this->hasResetKey) {
            $request = $this->getRequest();

            $this->hasResetKey = (boolean) $request->getParam($this->_resetKeyFieldName, false);
        }

        return $this->hasResetKey;
    }

    /**
     * The function that determines the element load order
     *
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function loadDefaultElements()
    {
        if ($this->hasResetKey()) {
            $this->getResetKeyElement();
        }
        $this->getOrganizationElement();
        $this->getUserNameElement();
        $this->getSubmitButton();
        $this->getLoginLinkElement();

        return $this;
    }

    /**
     * Is the form working in reset mode or not
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $hasKey
     * @return Gems_User_Form_ResetForm (continuation pattern)
     */
    public function setHasResetKey($hasKey = true)
    {
        $this->hasResetKey = $hasKey;

        return $this;
    }
}
