<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class PasswordChecker
{
    protected array $errors = [];

    protected ?User $user = null;

    public function __construct(
        protected readonly \Gems\Cache\HelperAdapter $cache,
        protected readonly array $config,
        protected readonly TranslatorInterface $translator,
    ) {
    }

    protected function _addError(string $errorMsg): void
    {
        $this->errors[] = $errorMsg;
    }

    /**
     * Test the password for minimum number of upper case characters.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function capsCount($parameter, $password)
    {
        $len = intval($parameter);
        if ($len && (preg_match_all('/[A-Z]/', $password ?? '') < $len)) {
            $this->_addError(sprintf(
                    $this->translator->plural('should contain at least one uppercase character', 'should contain at least %d uppercase characters', $len),
                    $len));
        }
    }

    /**
     * Add recursively the rules active for this specific set of codes.
     *
     * @param array $current The current (part)sub) array of $this->passwords to check
     * @param array $codes An array of code names that identify rules that should be used only for those codes.
     * @param array $rules The array that stores the activated rules.
     * @return void
     */
    protected function _getPasswordRules(array $current, array $codes, array &$rules)
    {
        foreach ($current as $key => $value) {
            if (is_array($value)) {
                // Only act when this is in the set of key values
                if (isset($codes[strtolower($key)])) {
                    $this->_getPasswordRules($value, $codes, $rules);
                }
            } else {
                $rules[$key] = $value;
            }
        }
    }

    /**
     * Get the rules active for this specific set of codes.
     *
     * @param array $codes An array of code names that identify rules that should be used only for those codes.
     * @return array
     */
    public function getPasswordRules(array $codes)
    {
        // Process the codes array to a format better used for filtering
        $codes = array_change_key_case(array_flip(array_filter($codes)));
        // \MUtil\EchoOut\EchoOut::track($codes);

        $rules = [];
        if (isset($this->config['password']) && is_array($this->config['password'])) {
            $this->_getPasswordRules($this->config['password'], $codes, $rules);
        }

        return $rules;
    }

    /**
     * Tests if the password appears on a (weak) password list. The list should
     * be a simpe newline separated list of (lowercase) passwords.
     *
     * @param string $parameter Filename of the password list, relative to APPLICATION_PATH
     * @param string $password  The password
     */
    protected function inPasswordList($parameter, $password)
    {
        if (empty($parameter)) {
            return;
        }

        if ($this->cache) {
            $passwordList = $this->cache->getCacheItem('weakpasswordlist');
        }

        if (empty($passwordList)) {
            $filename = __DIR__ . '/../../../docs/' . ltrim($parameter, '/');;

            if (! file_exists($filename)) {
                throw new \Gems\Exception("Unable to load password list '{$filename}'");
            }

            $passwordList = explode("\n", file_get_contents($filename));

            if ($this->cache) {
                $this->cache->setCacheItem('weakpasswordlist', $passwordList);
            }
        }

        if (null === $password) {
            $this->_addError($this->translator->trans('should not appear in the list of common passwords'));
        } elseif (in_array(strtolower($password), $passwordList)) {
            $this->_addError($this->translator->trans('appears in the list of common passwords'));
        }
    }

    /**
     * Test the password for minimum number of lower case characters.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function lowerCount($parameter, $password)
    {
        $len = intval($parameter);
        if ($len && (preg_match_all('/[a-z]/', $password ?? '') < $len)) {
            $this->_addError(sprintf(
                    $this->translator->plural('should contain at least one lowercase character', 'should contain at least %d lowercase characters', $len),
                    $len));
        }
    }

    /**
     * Test the password for maximum age (in days).
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function maxAge($parameter, $password)
    {
        $age = intval($parameter);

        if (is_null($password)) {
            // We return the description of this rule
            $this->_addError(sprintf($this->translator->trans('should be changed at least every %d days'), $age));
        } elseif ($age > 0 && !$this->user->isPasswordResetRequired() && $this->user->getPasswordAge() > $age) {
            // Skip this if we already should change the password
            $this->_addError(sprintf($this->translator->trans('has not been changed the last %d days and should be changed'), $age));
            $this->user->setPasswordResetRequired();
        }
    }

    /**
     * Test the password for minimum length.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function minLength($parameter, $password)
    {
        $len = intval($parameter);
        if ($len && (strlen($password ?? '') < $len)) {
            $this->_addError(sprintf($this->translator->trans('should be at least %d characters long'), $len));
        }
    }

    /**
     * Test the password for minimum number non letter characters.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function notAlphaCount($parameter, $password)
    {
        $len = intval($parameter);
        if ($len) {
            $count = strlen($password ?? '') - preg_match_all('/[A-Za-z]/', $password ?? '');
            if (($len > 0) && ($count < $len)) {
                $this->_addError(sprintf(
                        $this->translator->plural('should contain at least one non alphabetic character', 'should contain at least %d non alphabetic characters', $len),
                        $len));
            } elseif (($len < 0) && (($count > 0) || (null === $password))) {
                $this->_addError($this->translator->trans('should not contain non alphabetic characters'));
            }
        }
    }

    /**
     * Test the password for minimum number not alphanumeric characters.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function notAlphaNumCount($parameter, $password)
    {
        $len = intval($parameter);
        if ($len) {
            $count = strlen($password ?? '') - preg_match_all('/[0-9A-Za-z]/', $password ?? '');
            if (($len > 0) && ($count < $len)) {
                $this->_addError(sprintf(
                        $this->translator->plural('should contain at least one non alphanumeric character', 'should contain at least %d non alphanumeric characters', $len),
                        $len));
            } elseif (($len < 0) && (($count > 0) || (null === $password))) {
                $this->_addError($this->translator->trans('should not contain non alphanumeric characters'));
            }
        }
    }

    /**
     * The password should not contain the name of the user or the login name.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function notTheName($parameter, $password)
    {
        $on = $parameter != 0;
        if ($on) {
            $lpwd = strtolower($password ?? '');

            if ((false !== strpos($lpwd, strtolower($this->user->getLoginName()))) || (null === $password)) {
                $this->_addError($this->translator->trans('should not contain your login name'));
            }
        }
    }

    /**
     * Test the password for minimum number of numeric characters.
     *
     * @param mixed $parameter
     * @param string $password
     */
    protected function numCount($parameter, $password)
    {
        $len = intval($parameter);
        if ($len) {
            $count = preg_match_all('/[0-9]/', $password ?? '');
            if (($len > 0) && ($count < $len)) {
                $this->_addError(sprintf(
                        $this->translator->plural('should contain at least one number', 'should contain at least %d numbers', $len),
                        $len));
            } elseif (($len < 0) && (($count > 0) || (null === $password))) {
                $this->_addError($this->translator->trans('may not contain numbers'));
            }
        }
    }

    /**
     * Check for password weakness.
     *
     * @param User $user
     * @param string|null $password Or null when you want a report on all the rules for this password.
     * @param boolean $skipAge When setting a new password, we should not check for age
     * @return string[]|null String or array of strings containing warning messages
     */
    public function reportPasswordWeakness(\Gems\User\User $user, ?string $password, bool $skipAge = false): ?array
    {
        if (!$user->canSetPassword()) {
            return null;
        }

        $this->user = $user;
        $this->errors = [];

        $rules = $this->getPasswordRules($user->getPasswordCheckerCodes());

        if ($skipAge) {
            unset($rules['maxAge']);
        }

        foreach ($rules as $rule => $parameter) {
            if (method_exists($this, $rule)) {
                $this->$rule($parameter, $password);
            }
        }

        return $this->errors;
    }
}
