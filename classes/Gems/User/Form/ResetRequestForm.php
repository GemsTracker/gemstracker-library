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
 * @since      Class available since version 1.5.3
 */
class Gems_User_Form_ResetRequestForm extends \Gems_User_Form_OrganizationFormAbstract
{
    /**
     * The field name for the login link element
     *
     * @var string
     */
    protected $_loginLinkFieldName = 'loginlink';

    /**
     * Returns an html link for the login page.
     *
     * @return \MUtil_Html_AElement
     */
    public function getLoginLink()
    {
        return \MUtil_Html::create('a', array('controller' => 'index', 'action' => 'login'), $this->translate->_('Back to login'), array('class' => 'actionlink'));
    }

    /**
     * Returns a link to the login page
     *
     * @return \MUtil_Form_Element_Html
     */
    public function getLoginLinkElement()
    {
        $element = $this->getElement($this->_tokenFieldName);

        if (! $element) {
            // Login link
            if ($link = $this->getLoginLink()) {
                $element = new \MUtil_Form_Element_Html($this->_loginLinkFieldName);
                // $element->br();
                $element->setValue($link);

                $this->addElement($element);
            }

            return $element;
        }
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        return $this->translate->_('Request reset');
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
            $element = parent::getUserNameElement();

            //$element->addValidator(new \Gems_User_Validate_ResetRequestValidator($this, $this->translate));
        }

        return $element;
    }

    /**
     * The function that determines the element load order
     *
     * @return \Gems_User_Form_LoginForm (continuation pattern)
     */
    public function loadDefaultElements()
    {
        $this->getOrganizationElement();
        $this->getUserNameElement();
        $this->getSubmitButton();
        $this->getLoginLinkElement();

        return $this;
    }
}
