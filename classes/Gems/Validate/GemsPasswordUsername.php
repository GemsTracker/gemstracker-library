<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @version    $Id$
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * OBSOLETE, we now use Gems_Auth with a Zend_Auth_Adapter_DbTable
 *
 * @deprecated
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Validate_GemsPasswordUsername extends Zend_Validate_Db_Abstract
{
    /**
     * Error constants
     */
    const ERROR_DATABASE_NOT_INSTALLED     = 'notInstalled';
    const ERROR_PASSWORD_WRONG             = 'wrongPassword';
    const ERROR_PASSWORD_USERNAME_NOTFOUND = 'notFound';
    const ERROR_PASSWORD_USERNAME_NOTTHERE = 'notThere';
    const ERROR_PASSWORD_DELAY             = 'blockedDelay';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_DATABASE_NOT_INSTALLED     => 'Installation not complete! Login is not yet possible!',
        self::ERROR_PASSWORD_WRONG             => 'Wrong password.',
        self::ERROR_PASSWORD_USERNAME_NOTFOUND => 'Combination of username password not found.',
        self::ERROR_PASSWORD_USERNAME_NOTTHERE => 'Specify a password and username.',
        self::ERROR_PASSWORD_DELAY             => 'Your account is temporarily blocked, please wait %value% minutes'
    );

    protected $_passwordField;
    protected $_usernameField;

    /**
     * Exponent to use when calculating delay
     * @var int
     */
    protected $_delayFactor = 4;

    /**
     * Provides basic configuration for use with Zend_Validate_Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * The KeyFields are fields that occur as names in the context of the form and that
     * identify the current row - that can have the value.
     * A database adapter may optionally be supplied to avoid using the registered default adapter.
     *
     * @param string $usernameField The form field containing the login name
     * @param string $passwordField The form field containing the password
     * @param Zend_Db_Adapter_Abstract $adapter An optional database adapter to use.
     */
    public function __construct($usernameField, $passwordField, Zend_Db_Adapter_Abstract $adapter = null, $delayFactor = null)
    {
        parent::__construct('gems__staff', 'gsf_login', null, $adapter);

        $this->_usernameField = $usernameField;
        $this->_passwordField = $passwordField;

        if (isset($delayFactor)) {
            $this->_delayFactor = $delayFactor;
        }
    }

    public function isValid($value, $context = array())
    {
        if (isset($context[$this->_usernameField])) {
            $userinput = true;
            $username = $context[$this->_usernameField];
        } else {
            $userinput = false;
            $username  = $this->_usernameField;
        }
        $password = isset($context[$this->_passwordField]) ? $context[$this->_passwordField] : null;

        if ($username && $password) {

            /************************************
             * Project.ini super admin password *
             ************************************/
            $escortProject = GemsEscort::getInstance()->project;
            if (isset($escortProject->admin) && $escortProject->admin['user'] == $username) {
                if ($escortProject->admin['pwd'] == $password) {
                    return true;
                } else {
                    $this->_error(self::ERROR_PASSWORD_USERNAME_NOTFOUND);
                    return false;
                }
            }

            /*********************
             * Check in database *
             *********************/

            /**
             * Check for an adapter being defined. if not, fetch the default adapter.
             */
            if ($this->_adapter === null) {
                $this->_adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
                if (null === $this->_adapter) {
                    require_once 'Zend/Validate/Exception.php';
                    throw new Zend_Validate_Exception('No database adapter present');
                }
            }

            $condition = $this->_adapter->quoteIdentifier('gsf_password') . ' = ?';
            $this->_exclude = $this->_adapter->quoteInto($condition, md5($password));

            try {
                /**
                 * Lookup last failed login and number of failed logins
                 */
                try {
                    $sql = "SELECT gsf_failed_logins, UNIX_TIMESTAMP(gsf_last_failed)
                    AS gsf_last_failed FROM {$this->_table} WHERE gsf_login = ?";
                    $results = $this->_adapter->fetchRow($sql, array($username));
                } catch (Zend_Db_Exception $zde) {
                    //If we need to apply a db patch, just use a default value
                    $results = 0;
                    MUtil_Echo::r(GemsEscort::getInstance()->translate->_('Please update the database'));
                }

                $delay = pow($results['gsf_failed_logins'], $this->_delayFactor);
                $remaining = ($results['gsf_last_failed'] + $delay) - time();

                if ($results['gsf_failed_logins'] > 0 && $remaining > 0) {
                    $this->_obscureValue = false;
                    $this->_error(self::ERROR_PASSWORD_DELAY, ceil($remaining / 60));
                    return false;
                }

                if ($this->_query($username)) {
                    return true;
                } else {
                    if ($userinput) {
                        $this->_error(self::ERROR_PASSWORD_USERNAME_NOTFOUND);
                    } else {
                        $this->_error(self::ERROR_PASSWORD_WRONG);
                    }
                    return false;
                }
            } catch (Zend_Db_Exception $zde) {
                $this->_error(self::ERROR_DATABASE_NOT_INSTALLED);
                return false;
            }

        } else {
            $this->_error(self::ERROR_PASSWORD_USERNAME_NOTTHERE);
            return false;
        }
    }
}
