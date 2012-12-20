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
 * @version $Id$
 * @package    Gems
 * @subpackage AccessLog
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Logging class to log access to certaint controller/actions
 *
 * @author     Menno Dekker
 * @filesource
 * @package    Gems
 * @subpackage AccessLog
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_AccessLog
{
    private static $_log;

    private $_db;
    private $_sessionStore;
    private $_userInfo;

    /**
     * Convenience calls to the log method:
     *
     * When you try to call 'log' + anything it will be interpreted as a call to the log
     * method where the part following log is the action and the first argument is the
     * respondentId. When you want to utilize the full power of the logging system, use
     * the log method directly.
     *
     * @see log()
     *
     * @param <type> $name
     * @param array $arguments
     * @return <type>
     */
    public function __call($name, array $arguments)
    {
        if ('log' == substr($name, 0, 3)) {
            $respondentId = reset($arguments);
            $request      = next($arguments);
            if (!$request) $request = null;
            $logAction    = substr($name, 3);
            return $this->log($logAction, $request, null, $respondentId);
        }

        throw new exception(sprintf('Method %s does not exist', $name));
    }

    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
        $this->_sessionStore = new Zend_Session_Namespace(GEMS_PROJECT_NAME . APPLICATION_PATH . '__Gems__' . __CLASS__);
        $this->_userInfo = GemsEscort::getInstance()->session;
        $this->loadActions();
    }

    /**
     * Return an instance of the Gems_AccesLog class
     *
     * @param Zend_Db_Adapter_Abstract $db
     * @return Gems_AccessLog
     */
    public static function getLog(Zend_Db_Adapter_Abstract $db = null)
    {
        if (! self::$_log) {
            if (null === $db) {
                $db = Zend_Registry::get('db');
            }
            self::$_log = new self($db);
        }

        return self::$_log;
    }

    protected function getActionId($action)
    {
        if (! array_key_exists($action,  $this->_actions)) {
            //Check if a refresh fixes the problem
            $this->loadActions(true);
            if (! array_key_exists($action,  $this->_actions)) {
                $values['glac_name']    = $action;
                $values['glac_change']  = preg_match('/(save|add|store|delete|remove|create)/', $action);

                /*
                 * For 1.3 release the default behaviour is to disable logging for all actions,
                 * so we get an opt-in per action
                 */
                $values['glac_log']     = 0;

                /*
                 * Later on, we can set some rules like below to disable logging for
                 * actions like the autofilter
                 */
                //$values['glac_log']  = !substr_count($action, '.autofilter');
                $values['glac_created'] = new MUtil_Db_Expr_CurrentTimestamp();

                $this->_db->insert('gems__log_actions', $values);

                $this->loadActions(true);
            }
        }

        return $this->_actions[$action]['id'];
    }

    /**
     * Load the actions into memory, use optional parameter to enforce refreshing
     *
     * @param type $reset
     */
    public function loadActions($reset = false)
    {
        //When project escort doesn't implement the log interface, we disable logging and don't load actions
        if  (GemsEscort::getInstance() instanceof Gems_Project_Log_LogRespondentAccessInterface &&
             ($reset || (! isset($this->_actions)))) {

            $actions = GemsEscort::getInstance()->getUtil()->getAccessLogActions();
            if ($reset) {
                $actions->invalidateCache();
                //Now unset to force a reload
                unset(GemsEscort::getInstance()->getUtil()->accessLogActions);
                $actions = GemsEscort::getInstance()->getUtil()->getAccessLogActions();
            }

            $this->_actions = $actions->getAllData();
        }
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param string  $action
     * @param Zend_Controller_Request_Abstract $request
     * @param string  $message   An optional message to log with the action
     * @param <type>  $respondentId
     * @param boolean $force     Should we force the logentry to be inserted or should we try to skip duplicates? Default = false
     * @return Gems_AccessLog
     */
    public function log($action, Zend_Controller_Request_Abstract $request = null, $message = null, $respondentId = null, $force = false)
    {
        try {
            //When project escort doesn't implement the log interface, we disable logging
            if (!(GemsEscort::getInstance() instanceof Gems_Project_Log_LogRespondentAccessInterface)
                || (!isset($this->_userInfo->user_id) && $force === false ) ) {
                return $this;
            }

            /*
             * For backward compatibility, get the request from the frontcontroller when it
             * is not supplied in the
             */
            if (!($request instanceof Zend_Controller_Request_Abstract)) {
                $request = Zend_Controller_Front::getInstance()->getRequest();
            }

            $values['glua_to']           = $respondentId;
            $values['glua_message']      = $message;
            $values['glua_by']           = $this->_userInfo->user_id ? $this->_userInfo->user_id  : 0;
            $values['glua_organization'] = $this->_userInfo->user_organization_id ? $this->_userInfo->user_organization_id : 0;
            $values['glua_action']       = $this->getActionId($action);
            $values['glua_role']         = $this->_userInfo->user_role ? $this->_userInfo->user_role : '--not set--' ;
            $values['glua_created']      = new MUtil_Db_Expr_CurrentTimestamp();

            if ($request instanceof Zend_Controller_Request_Http) {
                $values['glua_remote_ip'] = $request->getClientIp();
            } else {
                $values['glua_remote_ip'] = '';
            }

            /*
             * Now we know for sure that the action is in the list, check if we
             * need to log this action
             */
            if (!($this->_actions[$action]['log']) && !$force) return $this;

            if (isset($this->_sessionStore->glua_action)) {
                //If we don't force a logentry, check if it is a duplicate
                if (!$force) {
                    if (($this->_sessionStore->glua_to == $values['glua_to']) &&
                        ($this->_sessionStore->glua_organization == $values['glua_organization']) &&
                        ($this->_sessionStore->glua_action == $values['glua_action']) &&
                        ($this->_sessionStore->glua_message == $values['glua_message'])) {

                        // Prevent double logging of nothing
                        // MUtil_Echo::r($values, 'Double');
                        return $this;
                    }
                }
            }
            // MUtil_Echo::r($values, 'Logged');

            //Now save the variables to the session to prevent duplicates if needed
            $this->_sessionStore->glua_to           = $values['glua_to'];
            $this->_sessionStore->glua_organization = $values['glua_organization'];
            $this->_sessionStore->glua_action       = $values['glua_action'];
            $this->_sessionStore->glua_message      = $values['glua_message'];

            $this->_db->insert('gems__log_useractions', $values);

            return $this;
        } catch (Exception $exc) {
            Gems_Log::getLogger()->logError($exc, $request);
            MUtil_Echo::r(GemsEscort::getInstance()->translate->_('Database needs to be updated!'));
            return $this;
        }
    }
}