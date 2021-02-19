<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
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
abstract class Gems_User_Form_OrganizationFormAbstract extends \Gems_Form_AutoLoadFormAbstract implements \Gems_User_Validate_GetUserInterface
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $_user;

    /**
     *
     * @var \Gems_Loader
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
     * @var \Zend_Controller_Request_Abstract
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
     * @var \Zend_Util
     */
    protected $util;

    /**
     * Get the organization id that has been currently entered
     * 
     * @return int
     */
    public function getActiveOrganizationId()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            return $request->getParam($this->organizationFieldName);
        }
    }

    /**
     * Returns the organization id that should currently be used for this form.
     *
     * @return int Returns the current organization id, if any
     */
    public function getCurrentOrganizationId()
    {
        $request    = $this->getRequest();
        if ($request->isPost() && ($orgId = $request->getParam($this->organizationFieldName))) {
            return $orgId;
        }

        $userLoader = $this->loader->getUserLoader();
        return $userLoader->getCurrentUser()->getCurrentOrganizationId();
    }

    /**
     *  Returns a list with the organizations the user can select for login.
     *
     * @return array orgId => Name
     */
    public function getLoginOrganizations()
    {
        $site = $this->util->getSites()->getSiteForCurrentUrl();
        
        return $site->getUrlOrganizations();
    }


    /**
     * Returns/sets an element for determining / selecting the organization.
     *
     * @return \Zend_Form_Element_Xhtml
     */
    public function getOrganizationElement()
    {
        $element = $this->getElement($this->organizationFieldName);
        $orgId   = $this->getCurrentOrganizationId();
        $orgs    = $this->getLoginOrganizations();
        $hidden  = $this->_organizationFromUrl || (count($orgs) < 2);

        if ($hidden) {
            if (! $element instanceof \Zend_Form_Element_Hidden) {
                $element = new \Zend_Form_Element_Hidden($this->organizationFieldName);
                $this->addElement($element);
            }

            if (! $this->_organizationFromUrl) {
                $orgIds = array_keys($orgs);
                $orgId  = reset($orgIds);
            }

            $element->setValue($orgId);

        } elseif (! $element instanceof \Zend_Form_Element_Select) {
            $element = $this->createElement('select', $this->organizationFieldName);
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
        return ! $this->getOrganizationElement() instanceof \Zend_Form_Element_Hidden;
    }

    /**
     * Return the Request object
     *
     * @return \Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (! $this->request) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
        }
        return $this->request;
    }

    /**
     * Returns a user
     *
     * @return \Gems_User_User
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
     * @return \Zend_Form_Element_Text
     */
    public function getUserNameElement()
    {
        $element = $this->getElement($this->usernameFieldName);

        if (! $element) {
            // Veld inlognaam
            $element = $this->createElement('text', $this->usernameFieldName);
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
        $this->_user = $this->loader->getUser(
                (isset($data[$this->usernameFieldName]) ? $data[$this->usernameFieldName] : null),
                (isset($data[$this->organizationFieldName]) ? $data[$this->organizationFieldName] : '')
            );

        return parent::isValid($data, $disableTranslateValidators);
    }

    /**
     * For small numbers of organizations a multiline selectbox will be nice. This
     * setting handles how many lines will display at once. Use 1 for the normal
     * dropdown selectbox
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param int $organizationMaxLines
     * @return \Gems_User_Form_LoginForm (continuation pattern)
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
        // If form was not (yet) populated, we can not use isChecked() so do this manually
        $request = $this->getRequest();
        if ($request->isPost() && strlen(trim($request->getPost($this->_submitFieldName)))) {
            return true;
        } else {
            return false;
        }
    }
}
