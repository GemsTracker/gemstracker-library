<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User\Validate;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class UserPasswordValidator extends \Gems\User\Validate\PasswordValidatorAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    private $_user;

    /**
     *
     * @param \Gems\User\User $user The user to check
     * @param string $message Default message for standard login fail.
     */
    public function __construct(\Gems\User\User $user, $message)
    {
        $this->_user = $user;

        parent::__construct($message);
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
        $result = $this->_user->authenticate($value);

        return $this->setAuthResult($result);
    }
}
