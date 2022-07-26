<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User\Form;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class ResetRequestForm extends \Gems\User\Form\OrganizationFormAbstract
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
     * @return \MUtil\Html\AElement
     */
    public function getLoginLink()
    {
        return \MUtil\Html::create('a', array('controller' => 'index', 'action' => 'login'), $this->translate->_('Back to login'), array('class' => 'actionlink'));
    }

    /**
     * Returns a link to the login page
     *
     * @return \MUtil\Form\Element\Html
     */
    public function getLoginLinkElement()
    {
        $element = $this->getElement($this->_tokenFieldName);

        if (! $element) {
            // Login link
            if ($link = $this->getLoginLink()) {
                $element = new \MUtil\Form\Element\Html($this->_loginLinkFieldName);
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

            //$element->addValidator(new \Gems\User\Validate\ResetRequestValidator($this, $this->translate));
        }

        return $element;
    }

    /**
     * The function that determines the element load order
     *
     * @return \Gems\User\Form\LoginForm (continuation pattern)
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
