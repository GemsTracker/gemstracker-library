<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_Validate_GetUserPasswordValidator extends \Gems_User_Validate_PasswordValidatorAbstract
{
    /**
     *
     * @var \Gems_User_Validate_GetUserInterface
     */
    private $_userSource;

    /**
     *
     * @param \Gems_User_Validate_GetUserInterface $userSource The source for the user
     * @param string $message Default message for standard login fail.
     */
    public function __construct(\Gems_User_Validate_GetUserInterface $userSource, $message)
    {
        $this->_userSource = $userSource;

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
        $user   = $this->_userSource->getUser();
        if ($user instanceof \Gems_User_User) {
            $result = $user->authenticate($value);
        } else {
            $result = new \Zend_Auth_Result(\Zend_Auth_Result::FAILURE_UNCATEGORIZED, null);
        }

        return $this->setAuthResult($result);
    }
}
