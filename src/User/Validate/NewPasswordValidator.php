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
use Laminas\Validator\ValidatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class NewPasswordValidator implements ValidatorInterface
{
    /**
     * The reported problems with the password.
     *
     * @var array
     */
    private array $report;

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
        $messages = [];

        $report = $this->passwordChecker->reportPasswordWeakness($this->user, $value, true);
        if (is_array($report)) {
            array_push($messages, ...$report);
        }

        if ($messages) {
            foreach ($messages as &$message) {
                $message = ucfirst($message) . '.';
            }
            $this->report = $messages;
        }

        // \MUtil\EchoOut\EchoOut::track($value, $this->report);

        return empty($this->report);
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
        if ($this->report) {
            return $this->report;

        } else {
            return array();
        }


    }
}
