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
     */
    const ERROR_DATABASE_NOT_INSTALLED     = 'notInstalled';
    const ERROR_PASSWORD_DELAY             = 'blockedDelay';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_DATABASE_NOT_INSTALLED     => 'Installation not complete! Login is not yet possible!',
        self::ERROR_PASSWORD_DELAY             => 'Your account is temporarily blocked, please wait %s minutes'
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

    public function __construct($db = null) {
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

    private function _error($code, $value1 = null, $value2 = null) {
        $messages = func_get_args();
        array_splice($messages, 0, 1, $this->_messageTemplates[$code]);
        return new Zend_Auth_Result($code, null, (array) $messages);
    }

    public function authenticate(Zend_Auth_Adapter_Interface $adapter, $username = '') {
        try {
            /**
             * Lookup last failed login and number of failed logins
             */
            try {
                $sql = "SELECT gsu_failed_logins, UNIX_TIMESTAMP(gsu_last_failed)
                AS gsu_last_failed FROM gems__users WHERE gsu_login = ?";
                $results = $this->db->fetchRow($sql, array($username));
            } catch (Zend_Db_Exception $zde) {
                //If we need to apply a db patch, just use a default value
                $results = 0;
                MUtil_Echo::r(GemsEscort::getInstance()->translate->_('Please update the database'));
            }

            $delay = pow($results['gsu_failed_logins'], $this->_delayFactor);
            $remaining = ($results['gsu_last_failed'] + $delay) - time();

            if ($results['gsu_failed_logins'] > 0 && $remaining > 0) {
                //$this->_obscureValue = false;
                $result = $this->_error(self::ERROR_PASSWORD_DELAY, ceil($remaining / 60));
            }
        } catch (Zend_Db_Exception $zde) {
            $result = $this->_error(self::ERROR_DATABASE_NOT_INSTALLED);
        }

        if (!isset($result)) {
            //Ok we are done without errors, now delegate to the Zend_Auth_Adapter
            $result = parent::authenticate($adapter);
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
    public function localize($result) {
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
                $message = $translate->_('Wrong password.');
                break;
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                $message = $translate->_('Combination of username password not found.');
                break;
        }

        //Now recombine with the others, they will be treated as params
        $messages  = array_merge((array) $message, (array) $messages);
        //Now do a sprintf if we have 1 or more params
        if (count($messages)>1) $messages = call_user_func_array('sprintf', $messages);

        return new Zend_Auth_Result($code, $identity, (array) $messages);
    }
}