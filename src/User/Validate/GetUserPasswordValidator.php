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

use Laminas\Authentication\Result;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class GetUserPasswordValidator extends \Gems\User\Validate\PasswordValidatorAbstract
{
    /**
     *
     * @var \Gems\User\Validate\GetUserInterface
     */
    private $_userSource;

    /**
     *
     * @param \Gems\User\Validate\GetUserInterface $userSource The source for the user
     * @param string $message Default message for standard login fail.
     */
    public function __construct(\Gems\User\Validate\GetUserInterface $userSource, $message)
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
        $user = $this->_userSource->getUser();
        if ($user instanceof \Gems\User\User) {
            $result = $user->authenticate($value);
        } else {
            $result = new Result(Result::FAILURE_UNCATEGORIZED, null);
        }

        return $this->setAuthResult($result);
    }
}
