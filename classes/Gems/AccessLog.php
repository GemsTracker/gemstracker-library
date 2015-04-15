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
 * @package    Gems
 * @subpackage AccessLog
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Logging class to log access to certaint controller/actions
 *
 * @author     Menno Dekker
 * @package    Gems
 * @subpackage AccessLog
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_AccessLog
{
    /**
     *
     * @var \Gems_AccessLog
     */
    private static $_log;

    /**
     *
     * @var \Gems_Util_AccessLogActions
     */
    private $_actions = array();

    /**
     *
     * @var \Zend_Cache_Core
     */
    private $_cache;

    /**
     *
     * @var string
     */
    private $_cacheId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    private $_db;

    /**
     *
     * @var \Gems_Loader
     */
    private $_loader;

    /**
     *
     * @var \Zend_Session_Namespace
     */
    private $_sessionStore;

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
     * @param string $name
     * @param array $arguments
     * @return \Gems_AccessLog
     * @deprecated Since 1.7.1
     */
    public function __call($name, array $arguments)
    {
        if ('log' == substr($name, 0, 3)) {
            $logAction    = substr($name, 3);
            $respondentId = reset($arguments);
            $request      = next($arguments);
            if (!$request) {
                $request = null;
            }

            return $this->log($logAction, $request, null, $respondentId);
        }

        throw new exception(sprintf('Method %s does not exist', $name));
    }

    /**
     *
     * @param \Zend_Cache_Core $cache
     * @param \Zend_Db_Adapter_Abstract $db
     * @param \Gems_Loader $loader
     */
    public function __construct(\Zend_Cache_Core $cache, \Zend_Db_Adapter_Abstract $db, \Gems_Loader $loader)
    {
        $this->_cache        = $cache;
        $this->_cacheId      = \MUtil_String::toCacheId(GEMS_PROJECT_NAME . APPLICATION_PATH . '__Gems__' . __CLASS__);
        $this->_db           = $db;
        $this->_loader       = $loader;
        $this->_sessionStore = new \Zend_Session_Namespace($this->_cacheId);

        $this->_actions = $this->_getActionsCache();
        if (false === $this->_actions) {
            $this->_actions = $this->_getActionsDb();
        }

        if (! self::$_log) {
            self::$_log = $this;
        }
    }

    /**
     * Load the actions into memory from the cache
     */
    private function _getActionsCache()
    {
        return $this->_cache->load($this->_cacheId);
    }

    /**
     * Load the actions into memory from the database (and cache them)
     */
    private function _getActionsDb()
    {
        try {
            $rows = $this->_db->fetchAssoc("SELECT * FROM gems__log_setup ORDER BY gls_name");
        } catch (Exception $exc) {
            $rows = array();

            if ($this->_loader->getCurrentUser()->isActive()) {
                \MUtil_Echo::r('Database needs to be updated!');
            }
        }

        $output = array();
        foreach ($rows as $row) {
            $output[$row['gls_name']] = $row;
        }

        // \MUtil_Echo::track($output);
        $this->_cache->save($output, $this->_cacheId, array('accesslog_actions'));

        return $output;
    }

    /**
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param string $actionId
     * @param boolean $changed
     * @param mixed $message
     * @param mixed $data
     * @param int $respondentId
     * @return boolean True when a log entry was stored
     */
    public function _logEntry(\Zend_Controller_Request_Abstract $request, $actionId, $changed, $message, $data, $respondentId)
    {
        $action      = $this->getAction($actionId);
        $currentUser = $this->_loader->getCurrentUser();
        $orgId       = $currentUser->getCurrentOrganizationId() ? $currentUser->getCurrentOrganizationId() : 0;

        // Exit when the user is not logged in and we should only track for logged in users
        if (! $currentUser->isActive()) {
            if (! $action['gls_when_no_user']) {
                return false;
            }
        }

        if ($request instanceof \Zend_Controller_Request_Http) {
            $post = $request->isPost();
            $ip   = $request->getClientIp();

            if ($post && (null === $data)) {
                $data = $request->getPost();
            }
        } else {
            $post = false;
            $ip   = '';
        }

        // Get type for second exit check
        if ($changed) {
            $checkKey = 'gls_on_change';
        } elseif ($post) {
            $checkKey = 'gls_on_post';
        } else {
            $checkKey = 'gls_on_action';
        }
        if (! $action[$checkKey]) {
            return false;
        }

        $values['gla_action']        = $action['gls_id_action'];
        $values['gla_respondent_id'] = $respondentId;

        $values['gla_by']            = $currentUser->getUserId();
        $values['gla_organization']  = $orgId;
        $values['gla_role']          = $currentUser->getRole() ? $currentUser->getRole() : '--not set--';

        $values['gla_changed']       = $changed ? 1 : 0;
        $values['gla_message']       = $this->_toText($message);
        $values['gla_data']          = $this->_toText($data);
        $values['gla_method']        = $post ? 'POST' : 'GET';
        $values['gla_remote_ip']     = $ip;

        return $this->_storeLogEntry($request, $values, $changed);
    }

    /**
     * Stores the current log entry
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param array $row
     * @param boolean $force     Should we force the logentry to be inserted or should we try to skip duplicates?
     * @return boolean True when a log entry was stored
     */
    private function _storeLogEntry(\Zend_Controller_Request_Abstract $request, array $row, $force)
    {
        if (! $force) {
            if (isset($this->_sessionStore->last) && ($row === $this->_sessionStore->last)) {
                return false;
            }

            // Now save the variables to the session to prevent duplicates if needed
            //
            // We skip $force as they are always saved and this prevents double logging in case of
            // e.g. a show => edit => show cycle
            $this->_sessionStore->last = $row;
        }
        try {
            $this->_db->insert('gems__log_activity', $row);
            return true;
        } catch (Exception $exc) {
            \Gems_Log::getLogger()->logError($exc, $request);
            \MUtil_Echo::r('Database needs to be updated!');
            return false;
        }
    }

    /**
     * Converts data types for storage
     *
     * @param mixed $data
     * @return string
     */
    private function _toText($data)
    {
        if (is_scalar($data)) {
            return $data;
        }
        return json_encode($data);
    }

    /**
     *
     * @param string $action
     * @return array
     */
    protected function getAction($action)
    {
        if (array_key_exists($action,  $this->_actions)) {
            return $this->_actions[$action];
        }

        // Check if a refresh from the db fixes the problem
        $this->_actions = $this->_getActionsDb();
        if (array_key_exists($action,  $this->_actions)) {
            return $this->_actions[$action];
        }

        $values['gls_name']         = $action;
        $values['gls_when_no_user'] = 0;
        $values['gls_on_action']    = 0;
        $values['gls_on_post']      = 0; // preg_match('/(create|edit)/', $action);
        $values['gls_on_change']    = preg_match('/(create|edit|delete)/', $action);

        $values['gls_changed']      = $values['gls_created']    = new \MUtil_Db_Expr_CurrentTimestamp();
        $values['gls_changed_by']   = $values['gls_created_by'] = \Gems_User_UserLoader::SYSTEM_USER_ID;

        $this->_db->insert('gems__log_setup', $values);

        $this->_actions = $this->_getActionsDb();

        if (array_key_exists($action,  $this->_actions)) {
            return $this->_actions[$action];
        }

        return array(
            'gls_id_action'    => 0,
            'gls_when_no_user' => 0,
            'gls_on_action'    => 0,
            'gls_on_post'      => 0,
            'gls_on_change'    => 0
            );
    }

    /**
     * Return an instance of the \Gems_AccesLog class
     *
     * @param \Zend_Db_Adapter_Abstract $db
     * @return \Gems_AccessLog
     * @deprecated since 1.7.1 Use accessLog source variable instead
     */
    public static function getLog()
    {
        if (! self::$_log) {
            throw new \Gems_Exception_Coding("AccessLog::getLog called before initialization.");
        }

        return self::$_log;
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param string  $action
     * @param \Zend_Controller_Request_Abstract $request
     * @param string  $message   An optional message to log with the action
     * @param <type>  $respondentId
     * @param boolean $force     Should we force the logentry to be inserted or should we try to skip duplicates? Default = false
     * @return \Gems_AccessLog
     * @deprecated Since version 1.7.1: use logChange or logRequest
     */
    public function log($action, \Zend_Controller_Request_Abstract $request = null, $message = null, $respondentId = null, $force = false)
    {
        $this->_logEntry($request, $action, $force, $message, null, $respondentId);

        return $this;
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param int $respondentId
     * @param mixed $message
     * @param mixed $data
     * @return boolean True when a log entry was stored
     */
    public function logChange(\Zend_Controller_Request_Abstract $request, $respondentId = null, $message = null, $data = null)
    {
        $action = $request->getControllerName() . '.' . $request->getActionName();
        return $this->_logEntry($request, $action, true, $message, $data, $respondentId);
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param int $respondentId
     * @param mixed $message
     * @param mixed $data
     * @return boolean True when a log entry was stored
     */
    public function logRequest(\Zend_Controller_Request_Abstract $request, $respondentId = null, $message = null, $data = null)
    {
        $action = $request->getControllerName() . '.' . $request->getActionName();
        return $this->_logEntry($request, $action, false, $message, $data, $respondentId);
    }
}