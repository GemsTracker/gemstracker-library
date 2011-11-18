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
 * @subpackage Auth
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Extension to the Zend_Auth framework in order to plug in extra features
 *
 * It adds translation to Zend_Auth_Result and performs the failed_login check with delay
 * after a previous failed attempt.
 *
 * @author     Menno Dekker
 * @filesource
 * @package    Gems
 * @subpackage Auth
 */
class Gems_Auth extends Zend_Auth
{
    /**
     * Error constants
     *
     * These must be numeric constants smaller than zero for
     * Zend_Auth_Result to work.
     */
    const ERROR_DATABASE_NOT_INSTALLED     = -11;
    const ERROR_PASSWORD_DELAY             = -12;

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_DATABASE_NOT_INSTALLED     => 'Installation not complete! Login is not yet possible!',
        self::ERROR_PASSWORD_DELAY             => 'Your account is temporarily blocked, please wait %s seconds'
    );

    /**
     * Exponent to use when calculating delay
     * @var int
     */
    protected $_delayFactor = 4;

    /**
     * @var Zend_Db_Table_Adapter
     */
    public $db;

    public function __construct($db = null)
    {
        /**
         * Check for an adapter being defined. if not, fetch the default adapter.
         */
        if ($db === null) {
            $this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
            if (null === $this->db) {
                require_once 'Zend/Validate/Exception.php';
                throw new Zend_Validate_Exception('No database adapter present');
            }
        } else {
            $this->db = $db;
        }
    }

    private function _error($code, $value1 = null, $value2 = null)
    {
        $messages = func_get_args();
        // array_splice($messages, 0, 1, $this->_messageTemplates[$code]);
        $messages[0] = $this->_messageTemplates[$code];
        return new Zend_Auth_Result($code, null, (array) $messages);
    }

    /**
     * Authenticates against the supplied adapter
     *
     * @param  Zend_Auth_Adapter_Interface $adapter
     * @param  array $formValues  We need information not in the adapter.
     * @return Zend_Auth_Result
     */
    public function authenticate(Zend_Auth_Adapter_Interface $adapter, array $formValues = null)
    {
        try {
            $login_name   = $formValues['userlogin'];
            $organization = $formValues['organization'];
            $sql = "SELECT gula_failed_logins, gula_last_failed FROM gems__user_login_attemps WHERE gula_login = ? AND gula_id_organization = ?";
            $values = $this->db->fetchRow($sql, array($login_name, $organization));

            if (! $values) {
                $values = array();
                $values['gula_login']           = $login_name;
                $values['gula_id_organization'] = $organization;
                $values['gula_failed_logins']   = 0;
                $values['gula_last_failed']     = null;

            } elseif ($values['gula_failed_logins'] > 0) {
                // Get the datetime
                $last  = new MUtil_Date($values['gula_last_failed'], Zend_Date::ISO_8601);

                // How long to wait until we can ignore the previous failed attempt
                $delay = pow($values['gula_failed_logins'], GemsEscort::getInstance()->project->getAccountDelayFactor());

                if (abs($last->diffSeconds()) <= $delay) {
                    // Response gets slowly slower
                    $sleepTime = min($values['gula_failed_logins'], 10);
                    sleep($sleepTime);
                    $remaining = $delay - abs($last->diffSeconds()) - $sleepTime;
                    if ($remaining>0) {
                        $result = $this->_error(self::ERROR_PASSWORD_DELAY, $remaining);
                    }
                }
            }
        } catch (Zend_Db_Exception $e) {
            // Fall through as this does not work if the database upgrade did not run
            // MUtil_Echo::r($e);
        }

        // We only forward to auth adapter when we have no timeout to prevent hammering the auth system
        if (! isset($result) ) {
            $result = parent::authenticate($adapter);
        }

        if ($result->isValid()) {
            $values['gula_failed_logins']   = 0;
            $values['gula_last_failed']     = null;
        } else {
            if ($values['gula_failed_logins']) {
                // MUtil_Echo::track($result->getCode(), self::ERROR_PASSWORD_DELAY);
                // Only increment when we have no password delay as the right password
                // will not be accepted when we are in the delay.
                if ($result->getCode() <> self::ERROR_PASSWORD_DELAY) {
                    $values['gula_failed_logins'] += 1;
                    $values['gula_last_failed'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');
                }
            } else {
                $values['gula_failed_logins'] = 1;
                $values['gula_last_failed'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');
            }
            $values['gula_failed_logins'] = max($values['gula_failed_logins'], 1);
        }

        try {
            if (isset($values['gula_login'])) {
                $this->db->insert('gems__user_login_attemps', $values);
            } else {
                $where = $this->db->quoteInto('gula_login = ? AND ', $login_name);
                $where .= $this->db->quoteInto('gula_id_organization = ?', $organization);
                $this->db->update('gems__user_login_attemps', $values, $where);
            }
        } catch (Zend_Db_Exception $e) {
            // Fall through as this does not work if the database upgrade did not run
            // MUtil_Echo::r($e);
        }

        //Now localize
        $result = $this->localize($result);

        return $result;
    }

    /**
     * Returns an instance of Gems_Auth
     *
     * Singleton pattern implementation
     *
     * @return Gems_Auth Provides a fluent interface
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Perform translation on an error message
     *
     * To make them showup in the .PO files, add the most common messages to
     * \library\Gems\languages\FakeTranslation.php
     * The first element in the message array is translated, while the following messages are
     * treated as sprintf parameters.
     *
     * @param Zend_Auth_Result $result
     * @return Zend_Auth_Result
     */
    public function localize($result)
    {
        $translate = GemsEscort::getInstance()->translate;
        $code      = $result->getCode();
        $identity  = $result->getIdentity();
        $messages  = $result->getMessages();

        //Shift the first message off, this is the one to translate
        $message   = $translate->_(array_shift($messages));

        /**
         * Now give a default message for some default error codes. This has the
         * positive side effect that we can remove some lines from FakeTranslations
         */
        switch ($code) {
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
            //    $message = $translate->_('Wrong password.');
            //    break;
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                $message = $translate->_('Combination of organization, username and password not found.');
                break;
        }

        // Recombine with the others if any, they will be treated as params
        if (count($messages)) {
            $messages  = array_merge((array) $message, (array) $messages);

            //Now do a sprintf if we have 1 or more params
            $messages = call_user_func_array('sprintf', $messages);
        } else {
            $messages = array($message);
        }

        return new Zend_Auth_Result($code, $identity, (array) $messages);
    }
}