<?php

/**
 * @package    Gems
 * @subpackage AccessLog
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
    private $_actions = false;

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
     * @var \Zend_Controller_Action_Helper_FlashMessenger
     */
    private $_messenger;

    /**
     * Data fields that contain a organization id
     *
     * @var array In preferred us order
     */
    private $_organizationIdFields = array(
        'gr2o_id_organization',
        'gr2t_id_organization',
        'gap_id_organization',
        'gec_id_organization',
        'gto_id_organization',
        'gor_id_organization',
        'gla_organization',
        'grco_organization',
    );

    /**
     * Cache for respondent id
     *
     * @var int
     */
    private $_respondentId;

    /**
     * Data fields that contain a respondent id
     *
     * @var array In preferred us order
     */
    private $_respondentIdFields = array(
        'grs_id_user',
        'gr2o_id_user',
        'gr2t_id_user',
        'gap_id_user',
        'gec_id_user',
        'gto_id_respondent',
        'grr_id_respondent',
        'gla_respondent_id',
        'grco_id_to',
    );

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
        $this->_cacheId      = \MUtil_String::toCacheId(GEMS_PROJECT_NAME . APPLICATION_PATH . '__gems__' . __CLASS__);
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

        } catch (\Exception $exc) {
            $rows = array();

            $this->_warn();
        }

        $output = array();
        foreach ((array) $rows as $row) {
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
     * @deprecated Since 1.7.2
     */
    public function _logEntry(\Zend_Controller_Request_Abstract $request, $actionId, $changed, $message, $data, $respondentId)
    {
        return $this->logEntry($request, $actionId, $changed, $message, $data, $respondentId);
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
        } catch (\Exception $exc) {
            \Gems_Log::getLogger()->logError($exc, $request);
            $this->_warn();
            return false;
        }
    }

    /**
     * Remove password and pwd contents and clean up message status data and single item arrays
     *
     * @param array $data
     * @return mixed
     */
    private function _toCleanArray(array $data)
    {
        switch (count($data)) {
            case 0:
                return null;

            case 1:
                if (isset($data[0])) {
                    // Return array content when only one item
                    // with the key 0.
                    if (is_array($data[0])) {
                        return $this->_toCleanArray($data[0]);
                    } else {
                        return $data[0];
                    }
                }
                break;

            case 2:
                if (isset($data[0], $data[1]) && is_string($data[1])) {
                    if (('info' === $data[1]) || ('warning' === $data[1]) || ('error' === $data[1])) {
                        if (is_array($data[0])) {
                            return $this->_toCleanArray($data[0]);
                        } else {
                            return $data[0];
                        }
                    }
                }
        }
        $output = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $output[$key] = $this->_toCleanArray($value);
            } else {
                if (is_string($value)) {
                    // Filter passwords from the log
                    if (\MUtil_String::contains($key, 'password', true) || \MUtil_String::contains($key, 'pwd', true)) {
                        $value = '****';
                    }
                } elseif ($value instanceof Zend_Date) {
                    // Output iso representation for date objects
                    $value = $value->getIso();
                }
                $output[$key] = $value;
            }
        }

        return $output;
    }

    /**
     * Converts data types for storage
     *
     * @param mixed $data
     * @return string
     */
    private function _toJson($data)
    {
        if ($data) {
            if (is_array($data)) {
                return json_encode($this->_toCleanArray($data));
            }

            return json_encode($data);
        }
    }

    /**
     * Send a warning, if not already done
     *
     * @staticvar boolean $warn
     */
    private function _warn()
    {
        static $warn = true;

        if ($warn) {
            \MUtil_Echo::r('Database needs to be updated, tables missing!');
            $warn = false;
        }
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
        $values['gls_on_change']    = preg_match(
                '/(create|edit|delete|deactivate|reactivate|import|export|recalc|check|synchronize|run|patch)/',
                $action
                );

        $values['gls_changed']      = $values['gls_created']    = new \MUtil_Db_Expr_CurrentTimestamp();
        $values['gls_changed_by']   = $values['gls_created_by'] = \Gems_User_UserLoader::SYSTEM_USER_ID;

        try {
            $this->_db->insert('gems__log_setup', $values);

            $this->_actions = $this->_getActionsDb();

            if (array_key_exists($action,  $this->_actions)) {
                return $this->_actions[$action];
            }
        } catch (\Exception $exc) {
            $this->_warn();
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
     * The curent flash messenger messages
     *
     * @return array
     */
    protected function getMessages()
    {
        if (! $this->_messenger instanceof \MUtil_Controller_Action_Helper_FlashMessenger) {
            $this->_messenger = new \MUtil_Controller_Action_Helper_FlashMessenger();
        }

        return $this->_messenger->getMessagesOnly();
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param string  $action
     * @param \Zend_Controller_Request_Abstract $request
     * @param string  $message   An optional message to log with the action
     * @param int     $respondentId
     * @param boolean $force     Should we force the logentry to be inserted or should we try to skip duplicates? Default = false
     * @return \Gems_AccessLog
     * @deprecated Since version 1.7.1: use logChange or logRequest
     */
    public function log($action, \Zend_Controller_Request_Abstract $request = null, $message = null, $respondentId = null, $force = false)
    {
        if (null === $request) {
            $request = \Zend_Controller_Front::getInstance()->getRequest();
        }
        $this->_logEntry($request, $action, $force, null, $message, $respondentId);

        return $this;
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param mixed $message
     * @param mixed $data
     * @param int $respondentId
     * @return boolean True when a log entry was stored
     */
    public function logChange(\Zend_Controller_Request_Abstract $request, $message = null, $data = null, $respondentId = null)
    {
        $action = $request->getControllerName() . '.' . $request->getActionName();
        return $this->logEntry($request, $action, true, $message, $data, $respondentId);
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
    public function logEntry(\Zend_Controller_Request_Abstract $request, $actionId, $changed, $message, $data, $respondentId)
    {
        $action      = $this->getAction($actionId);
        $currentUser = $this->_loader->getCurrentUser();

        if ($respondentId) {
            $this->_respondentId = $respondentId;
        }

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
        if (null === $message) {
            $message = $this->getMessages();
        }

        if (! $respondentId) {
            // FallBack in case nothing is in $data
            $respondentId = $this->_respondentId;
            if (is_array($data)) {
                foreach ($this->_respondentIdFields as $field) {
                    if (isset($data[$field]) && $data[$field]) {
                        $respondentId = $data[$field];
                        break;
                    }
                }
            }
        }

        $orgId = $currentUser->getCurrentOrganizationId() ? $currentUser->getCurrentOrganizationId() : 0;
        // When not respondentId, we don't need to look for the orgid in the data as it can be an array
        if (is_array($data) && $respondentId) {
            foreach ($this->_organizationIdFields as $field) {
                if (isset($data[$field]) && $data[$field]) {
                    $orgId = $data[$field];
                    break;
                }
            }
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
        $values['gla_message']       = $this->_toJson($message);
        $values['gla_data']          = $this->_toJson($data);
        $values['gla_method']        = $post ? 'POST' : 'GET';
        $values['gla_remote_ip']     = $ip;

        return $this->_storeLogEntry($request, $values, $changed);
    }

    /**
     * Logs the action for the current user with optional message and respondent id
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param mixed $message
     * @param mixed $data
     * @param int|\Gems_Tracker_Respondent $respondentId
     * @return boolean True when a log entry was stored
     */
    public function logRequest(\Zend_Controller_Request_Abstract $request, $message = null, $data = null, $respondentId = null)
    {
        $action = $request->getControllerName() . '.' . $request->getActionName();
        if ($respondentId instanceof \Gems_Tracker_Respondent) {
            if ($respondentId->exists) {
                $data = (array) $data;
                $data['gr2o_id_organization'] = $respondentId->getOrganizationId();
                $respondentId = $respondentId->getId();
            } else {
                $respondentId = null;
            }
        }
        return $this->logEntry($request, $action, false, $message, $data, $respondentId);
    }
}