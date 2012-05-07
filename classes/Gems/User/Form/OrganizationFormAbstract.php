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
 * @version    $id: OrganizationFormAbstract.php 203 2012-01-01t 12:51:32Z matijs $
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
abstract class Gems_User_Form_OrganizationFormAbstract extends Gems_Form_AutoLoadFormAbstract implements Gems_User_Validate_GetUserInterface
{
    /**
     * When true the organization was derived from the the url
     *
     * @var boolean
     */
    protected $_organizationFromUrl = false;

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
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

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

            $element->setValue($orgId);

        } elseif (! $element instanceof Zend_Form_Element_Select) {
            $element = new Zend_Form_Element_Select($this->organizationFieldName);
            $element->setLabel($this->translate->_('Organization'));
            $element->setRegisterInArrayValidator(true);
            $element->setRequired(true);
            $element->setMultiOptions($orgs);

            if ($this->organizationMaxLines > 1) {
                $element->setAttrib('size', min(count($orgs) + 1, $this->organizationMaxLines));
            }
            $this->addElement($element);

            $element->setValue($orgId);
        }

        return $element;
    }

    /**
     * Returns true when the organization element is visible to the user.
     *
     * @return boolean
     */
    public function getOrganizationIsVisible()
    {
        return ! $this->getOrganizationElement() instanceof Zend_Form_Element_Hidden;
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
     * Returns a user
     *
     * @return Gems_User_User
     */
    public function getUser()
    {
        if (! $this->_user) {
            $request = $this->getRequest();

            $this->_user = $this->loader->getUser($request->getParam($this->usernameFieldName), $request->getParam($this->organizationFieldName));
        }
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
     * True when this form was submitted.
     *
     * @return boolean
     */
    public function wasSubmitted()
    {
        return $this->getSubmitButton()->isChecked();
    }
}
