<?php

/**
 * @package    Gems
 * @subpackage Roles
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Log\LogHelper;

/**
 * This is the generic Roles class
 *
 * It loads the ACL in two stages when there is no db present, otherwise it just loads from the db:
 *
 * 1. $this->loadDefaultRoles()
 * 2. $this->loadDefaultPrivileges()
 * Normally you should not touch this to make upgrading easier
 *
 *
 * @package    Gems
 * @subpackage Roles
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Roles
{
    /**
     *
     * @var \Gems\Cache\HelperAdapter
     */
    protected $_cache = null;

    /**
     * The id used in the cache
     *
     * @var string
     */
    protected $_cacheid = 'gems_acl';

    /**
     *
     * @var \MUtil_Acl
     */
    protected $_acl;

    /**
     *
     * @var \Gems_Roles
     */
    private static $_instanceOfSelf;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Needed for being able to store role id's instead of role names in the db
     *
     * @var array role_id => role_name
     */
    private $_roleTranslations = array();

    /**
     * Pass any strange call to \MUtil_Acl
     *
     * @param stringethod
     * @param mixed $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($this->_acl instanceof \Zend_Acl && method_exists($this->_acl, $method)) {
            return call_user_func_array(array($this->_acl, $method), $args);
        }
    }

    /**
     *
     * @param mixed $cache HelperAdapter
     */
    public function __construct($cache = null, $logger = null)
    {
        self::$_instanceOfSelf = $this;

        $this->setCache($cache);

        if ($logger instanceof \Psr\Log\LoggerInterface) {
            $this->setLogger($logger);
        } elseif (($cache instanceof \GemsEscort) && ($cache->logger instanceof \Psr\Log\LoggerInterface)) {
            $this->setLogger($cache->logger);
        }

        $this->load();
    }

    /**
     * Empty this cache instance
     */
    private function _deleteCache()
    {
        if ($this->_cache instanceof \Gems\Cache\HelperAdapter) {
            $this->_cache->deleteItem($this->_cacheid);
            $this->_cache->deleteItem($this->_cacheid . 'trans');
        }
    }

    /**
     * Recursively expands roles into \Zend_Acl_Role objects
     *
     * @param array  $roleList
     * @param string $roleName
     */
    private function _expandRole(&$roleList, $roleName, $depth = 0)
    {
        $role = $roleList[$roleName];

        if (isset($role['marked']) && $role['marked']) {
            return;
        }

        // possible circular reference!
        if ($depth > 5) {
            throw new \Exception("Possible circular reference detected while expanding role '{$roleName}'");
        }

        if (!empty($role['grl_parents'])) {
            $parents = $this->translateToRoleNames($role['grl_parents']);

            foreach ($parents as $parent) {
                $this->_expandRole($roleList, $parent, $depth + 1);
            }
        } else {
            $parents = array();
        }

        $this->_acl->addRole(new \Zend_Acl_Role($role['grl_name']), $parents);

        $privileges = array_filter(array_map('trim', explode(",", $role['grl_privileges'])));
        if ($privileges) {
            $this->_acl->addPrivilege($role['grl_name'], $privileges);
        }

        $roleList[$roleName]['marked'] = true;
    }

    /**
     * Reset de ACL en bouw opnieuw op
     */
    private function _initAcl()
    {
        $this->_acl = new \MUtil_Acl();

        if (get_class(self::$_instanceOfSelf)!=='Gems_Roles') {
            throw new \Gems_Exception_Coding("Don't use project specific roles file anymore, you can now do so by using the gems_roles tabel and setup->roles from the interface.");
        }
        // Probeer eerst uit db in te lezen met fallback als dat niet lukt
        try {
            $this->loadDbAcl();

        } catch (\Exception $e) {

            if (! \Zend_Session::$_unitTestEnabled) {
                $this->_logger->error(LogHelper::getMessageFromException($e));
            }

            // Reset all roles
            unset($this->_acl);
            $this->_acl = new \MUtil_Acl();

            //Voeg standaard rollen en privileges in
            $this->loadDefaultRoles();
            $this->loadDefaultPrivileges();
        }

        // Now allow 'master' all access, except for the actions that have the
        // nologin privilege (->the login action)
        if (!$this->_acl->hasRole('master')) {
            //Add role if not already present
            $this->_acl->addRole('master');
        }
        $this->_acl->allow('master');
        $this->_acl->deny('master', null, 'pr.nologin');
    }

    /**
     * Save to cache
     *
     * @throws \Gems_Exception
     */
    private function _save()
    {
        if ($this->_cache instanceof \Gems\Cache\HelperAdapter) {
            if (! (
                $this->_cache->setCacheItem($this->_cacheid, $this->_acl, ['roles']) &&
                $this->_cache->setCacheItem($this->_cacheid . 'trans', $this->_roleTranslations, ['roles'])
            )) {
                throw new \Gems_Exception('Failed to save acl to cache');
            }
        }
    }

    /**
     * Maak een nieuwe ACL aan, omdat de cache verlopen is, of omdat de acl gewijzigd is.
     *
     * @return void
     */
    public function build()
    {
        $this->_deleteCache();
        $this->_initAcl();
        try {
            $this->_save();
        } catch (\Gems_Exception $e) {
            if ($this->_logger instanceof \Psr\Log\LoggerInterface) {
                $this->_logger->error($e->getMessage());
            }
        }
    }

    /**
     *
     * @return \MUtil_Acl
     */
    public function getAcl()
    {
        return $this->_acl;
    }

    /**
     * Static acces function
     *
     * @return \Gems_Roles
     */
    public static function getInstance()
    {
        if (!isset(self::$_instanceOfSelf)) {
            $c = __CLASS__;
            self::$_instanceOfSelf = new $c;
        }
        return self::$_instanceOfSelf;
    }

    /**
     * Load the ACL values either from the cache or from build()
     */
    public function load()
    {
        if ($this->_cache instanceof \Gems\Cache\HelperAdapter) {
            $cache = $this->_cache;
            if (! ($cache->hasItem($this->_cacheid) && $cache->hasItem($this->_cacheid . 'trans'))) {
                // cache miss
                $this->build();
            } else {
                // cache hit
                $this->_acl = $cache->getCacheItem($this->_cacheid);
                $this->_roleTranslations = $cache->getCacheItem($this->_cacheid . 'trans');
            }
        } else {
            $this->build();
        }
    }

    /**
     * Load access control list from db
     * @throws \Exception
     */
    public function loadDbAcl()
    {
        $db = \Zend_Registry::get('db');

        $sql = "SELECT grl_id_role, grl_name, grl_privileges, grl_parents FROM gems__roles";

        $roles = $db->fetchAll($sql);

        if (empty($roles)) {
            throw new \Exception("No roles stored in db");
        }

        // Set role id to name tranlations
        foreach ($roles as $role) {
            $this->_roleTranslations[$role['grl_id_role']] = $role['grl_name'];
        }
        $roleList = array_combine(array_map(function($value) { return $value['grl_name']; }, $roles), $roles);

        foreach ($roleList as $role) {
            $this->_expandRole($roleList, $role['grl_name']);
        }

        return true;
    }

    public function loadDefaultPrivileges()
    {
        /**
         * Only add the nologin role, as the others should come from the database when it is initialized
         */
        $this->_acl->addPrivilege(
            'nologin',
            'pr.contact.bugs', 'pr.contact.support',
            'pr.nologin'
        );
    }

    public function loadDefaultRoles()
    {
        /**
         * Only add the nologin role, as the others should come from the database when it is initialized
         */
        $this->_acl->addRole(new \Zend_Acl_Role('nologin'));
    }

    /**
     *
     * @param mixed $cache
     */
    public function setCache(\Gems\Cache\HelperAdapter $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Translate string role id to numeric role id
     *
     * @param string $role
     * @return array Of role id's
     */
    public function translateToRoleId($role)
    {
        $lookup = array_flip($this->_roleTranslations);

        if (isset($lookup[$role])) {
            return $lookup[$role];
        }

        return $role;
    }

    /**
     * Translate all string role id's to numeric role ids
     *
     * @param mixed $roles string or array
     * @return array Of role id's
     */
    public function translateToRoleIds($roles)
    {
        if (!is_array($roles)) {
            if ($roles) {
                $roles = explode(",", $roles);
            } else {
                $roles = array();
            }
        }

        $lookup = array_flip($this->_roleTranslations);

        foreach ($roles as $key => $role) {
            if (isset($lookup[$role])) {
                $roles[$key] = $lookup[$role];
            }
        }

        return $roles;
    }

    /**
     * Translate numeric role id to string name
     *
     * @param int $role
     * @return string
     */
    public function translateToRoleName($role)
    {
        if (isset($this->_roleTranslations[$role])) {
            return $this->_roleTranslations[$role];
        }

        return $role;
    }

    /**
     * Translate all numeric role id's to string names
     *
     * @param mixed $roles string or array
     * @return array
     */
    public function translateToRoleNames($roles)
    {
        if (!is_array($roles)) {
            if ($roles) {
                $roles = explode(",", $roles);
            } else {
                $roles = array();
            }
        }
        foreach ($roles as $key => $role) {
            if (isset($this->_roleTranslations[$role])) {
                $roles[$key] = $this->_roleTranslations[$role];
            }
        }

        return $roles;
    }
}
