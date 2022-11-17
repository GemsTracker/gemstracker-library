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

use Gems\User\PasswordChecker;
use Gems\User\User;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class NewPasswordValidator implements \Zend_Validate_Interface
{
    /**
     * The reported problems with the password.
     *
     * @var array or null
     */
    private $_report;

    /**
     *
     * @param \Gems\User\User $user The user to check
     */
    public function __construct(
        private readonly User $user,
        private readonly PasswordChecker $passwordChecker,
    ) {
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
        $this->_report = $this->passwordChecker->reportPasswordWeakness($this->user, $value, true);

        foreach ($this->_report as &$report) {
            $report = ucfirst($report) . '.';
        }

        // \MUtil\EchoOut\EchoOut::track($value, $this->_report);

        return ! (boolean) $this->_report;
    }

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        if ($this->_report) {
            return $this->_report;

        } else {
            return array();
        }


    }
}
