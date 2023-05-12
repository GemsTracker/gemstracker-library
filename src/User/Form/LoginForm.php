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
 * @since      Class available since version 1.5
 */
class LoginForm extends \Gems\User\Form\OrganizationFormAbstract
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
            $options['class'] .= ' login-form';
        } else {
            $options['class'] = 'login-form';
        }
        parent::__construct($options);
    }

    /**
     * Returns/sets a link to the reset password page
     *
     * @return \MUtil\Form\Element\Html
     */
    public function getLostPasswordElement()
    {
        $element = $this->getElement($this->_lostPasswordFieldName);

        if (! $element) {
            // Reset password
            $element = new \MUtil\Form\Element\Html($this->_lostPasswordFieldName);
            // $element->br();
            $element->setValue($this->getLostPasswordLink());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns an html link to the reset password page
     *
     * @return \MUtil\Html\AElement
     */
    public function getLostPasswordLink()
    {
        return new \MUtil\Html\AElement(array('controller' => 'index', 'action' => 'resetpassword'), $this->translate->_('Lost password'), array('class' => 'actionlink'));
    }

    /**
     * Returns/sets a password element.
     *
     * @return \Zend_Form_Element_Password
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

            if ($this->getOrganizationElement() instanceof \Zend_Form_Element_Hidden) {
                $explain = $this->translate->_('Combination of user and password not found.');
            } else {
                $explain = $this->translate->_('Combination of user and password not found for this organization.');
            }
            $element->addValidator(new \Gems\User\Validate\GetUserPasswordValidator($this, $explain));

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns the password entered
     *
     * @return string
     */
    public function getPasswordText()
    {
        return $this->request->getParam($this->passwordFieldName);
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
     * @return \MUtil\Form\Element\Html
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
     * @return \MUtil\Html\AElement
     */
    public function getTokenLink()
    {
        return \MUtil\Html::create('a', array('controller' => 'ask', 'action' => 'token'), $this->translate->_('Enter your token...'), array('class' => 'actionlink'));
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
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $showPasswordLost
     * @return \Gems\User\Form\LoginForm (continuation pattern)
     */
    public function setShowPasswordLost($showPasswordLost = true)
    {
        $this->showPasswordLost = $showPasswordLost;

        return $this;
    }

    /**
     * The default behaviour for showing an 'ask token' button
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $showToken
     * @return \Gems\User\Form\LoginForm (continuation pattern)
     */
    public function setShowToken($showToken = true)
    {
        $this->showToken = $showToken;

        return $this;
    }
}
