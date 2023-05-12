<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User\Validate;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class ResetRequestValidator implements \Zend_Validate_Interface
{
    /**
     * The error message
     *
     * @var string
     */
    private $_message;

    /**
     *
     * @var \Gems\User\Validate\GetUserInterface
     */
    private $_userSource;

    /**
     *
     * @var \Zend_Translate
     */
    private $translate;

    /**
     *
     * @param \Gems\User\Validate\GetUserInterface $userSource The source for the user
     * @param \Zend_Translate $translate
     */
    public function __construct(\Gems\User\Validate\GetUserInterface $userSource, \Zend_Translate $translate)
    {
        $this->_userSource = $userSource;
        $this->translate   = $translate;
    }

    /**
     *
     * @param string $message Default message for standard login fail.
     */
    public function getMessages()
    {
        return array($this->_message);
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @param  mixed $content
     * @return boolean
     * @throws \Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = array())
    {
        $this->_message = null;

        $user = $this->_userSource->getUser();

        If (! ($user->isActive() && $user->canResetPassword() && $user->isAllowedOrganization($context['organization']))) {
            $this->_message = $this->translate->_('User not found or no e-mail address known or user cannot be reset.');
        }

        return (boolean) ! $this->_message;
    }
}
