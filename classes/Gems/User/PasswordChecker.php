<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
class Gems_User_PasswordChecker extends \MUtil_Registry_TargetAbstract
{
    /**
     *
     * @var array
     */
    protected $_errors = array();

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Zend_Cache
     */
    protected $cache;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Gems_User_User $user
     */
    protected $user;

    /**
     *
     * @param type $errorMsg
     */
    protected function _addError($errorMsg)
    {
        $this->_errors[] = $errorMsg;
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
        $results = array();
        if ($len && (preg_match_all('/[A-Z]/', $password, $results) < $len)) {
            $this->_addError(sprintf(
                    $this->translate->plural('should contain at least one uppercase character', 'should contain at least %d uppercase characters', $len),
                    $len));
        }
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
            $passwordList = $this->cache->load('weakpasswordlist');
        }

        if (empty($passwordList)) {
            $filename = __DIR__ . '/../../../docs/' . ltrim($parameter, '/');;

            if (! file_exists($filename)) {
                throw new \Gems_Exception("Unable to load password list '{$filename}'");
            }

            $passwordList = explode("\n", file_get_contents($filename));

            if ($this->cache) {
                $this->cache->save($passwordList, 'weakpasswordlist');
            }
        }

        if (null === $password) {
            $this->_addError($this->translate->_('should not appear in the list of common passwords'));
        } elseif (in_array(strtolower($password), $passwordList)) {
            $this->_addError($this->translate->_('appears in the list of common passwords'));
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
        $results = array();
        if ($len && (preg_match_all('/[a-z]/', $password, $results) < $len)) {
            $this->_addError(sprintf(
                    $this->translate->plural('should contain at least one lowercase character', 'should contain at least %d lowercase characters', $len),
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
            $this->_addError(sprintf($this->translate->_('should be changed at least every %d days'), $age));
        } elseif ($age > 0 && !$this->user->isPasswordResetRequired() && $this->user->getPasswordAge() > $age) {
            // Skip this if we already should change the password
            $this->_addError(sprintf($this->translate->_('has not been changed the last %d days and should be changed'), $age));
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
        if ($len && (strlen($password) < $len)) {
            $this->_addError(sprintf($this->translate->_('should be at least %d characters long'), $len));
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
            $results = array(); // Not used but required
            $count   = strlen($password) - preg_match_all('/[A-Za-z]/', $password, $results);
            if (($len > 0) && ($count < $len)) {
                $this->_addError(sprintf(
                        $this->translate->plural('should contain at least one non alphabetic character', 'should contain at least %d non alphabetic characters', $len),
                        $len));
            } elseif (($len < 0) && (($count > 0) || (null === $password))) {
                $this->_addError($this->translate->_('should not contain non alphabetic characters'));
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
            $results = array(); // Not used but required
            $count   = strlen($password) - preg_match_all('/[0-9A-Za-z]/', $password, $results);
            if (($len > 0) && ($count < $len)) {
                $this->_addError(sprintf(
                        $this->translate->plural('should contain at least one non alphanumeric character', 'should contain at least %d non alphanumeric characters', $len),
                        $len));
            } elseif (($len < 0) && (($count > 0) || (null === $password))) {
                $this->_addError($this->translate->_('should not contain non alphanumeric characters'));
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
            $lpwd = strtolower($password);

            if ((false !== strpos($lpwd, strtolower($this->user->getLoginName()))) || (null === $password)) {
                $this->_addError($this->translate->_('should not contain your login name'));
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
            $results = array(); // Not used but required
            $count   = preg_match_all('/[0-9]/', $password, $results);
            if (($len > 0) && ($count < $len)) {
                $this->_addError(sprintf(
                        $this->translate->plural('should contain at least one number', 'should contain at least %d numbers', $len),
                        $len));
            } elseif (($len < 0) && (($count > 0) || (null === $password))) {
                $this->_addError($this->translate->_('may not contain numbers'));
            }
        }
    }

    /**
     * Check for password weakness.
     *
     * @param \Gems_User_User $user
     * @param string $password Or null when you want a report on all the rules for this password.
     * @param array  $codes An array of code names that identify rules that should be used only for those codes.
     * @param boolean $skipAge When setting a new password, we should not check for age
     * @return mixed String or array of strings containing warning messages
     */
    public function reportPasswordWeakness(\Gems_User_User $user, $password, array $codes, $skipAge = false)
    {
        $this->user = $user;
        $this->_errors = array();

        $rules = $this->project->getPasswordRules($codes);

        if ($skipAge) {
            unset($rules['maxAge']);
        }
        \MUtil_Echo::track($rules);
        foreach ($rules as $rule => $parameter) {
            if (method_exists($this, $rule)) {
                $this->$rule($parameter, $password);
            }
        }
        // \MUtil_Echo::track($this->_errors);

        return $this->_errors;
    }
}
