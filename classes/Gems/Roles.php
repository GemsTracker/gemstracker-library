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
 * @subpackage Roles
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

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
 * @version    $Id$
 */
class Gems_Roles
{

    protected $_cache = null;
    protected $_cacheid = 'gems_acl';

    /**
     *
     * @var MUtil_Acl
     */
    protected $_acl;

    private static $_instanceOfSelf;

    public function __call($method, $args) {
        if ($this->_acl instanceof Zend_Acl && method_exists($this->_acl, $method)) {
            return call_user_func_array(array($this->_acl, $method), $args);
        }
    }

    public function __construct($cache = null) {
        self::$_instanceOfSelf = $this;
        $this->setCache($cache);
        if (!is_null($cache))
            $this->load();
    }

    /**
     * Maak een nieuwe ACL aan, omdat de cache verlopen is, of omdat de acl gewijzigd is.
     *
     * @return unknown_type
     */
    public function build() {
        $this->deleteCache();
        $this->initAcl();
        $this->save();
    }

    private function cmp($a, $b) {
        return strcmp($a['name'], $b['name']);
    }

    private function deleteCache() {
        $cache = $this->_cache;
        if ($this->_cache instanceof Zend_Cache_Core)
            $cache->remove($this->_cacheid);
    }

    /**
     *
     * @return MUtil_Acl
     */
    public function getAcl() {
        return $this->_acl;
    }

    public static function getInstance()
    {
        if (!isset(self::$_instanceOfSelf)) {
            $c = __CLASS__;
            self::$_instanceOfSelf = new $c;
        }
        return self::$_instanceOfSelf;
    }

    /**
     * Reset de ACL en bouw opnieuw op
     */
    private function initAcl() {
        $this->_acl = new MUtil_Acl();
        if (get_class(self::$_instanceOfSelf)!=='Gems_Roles') {
            throw new Gems_Exception_Coding("Don't use project specific roles file anymore, you can now do so by using the gems_roles tabel and setup->roles from the interface.");
        }
        // Probeer eerst uit db in te lezen met fallback als dat niet lukt
        try {
            $this->loadDbAcl();
        } catch (Exception $e) {
            Gems_Log::getLogger()->logError($e);

            // Reset all roles
            unset($this->_acl);
            $this->_acl = new MUtil_Acl();

            //Voeg standaard rollen en privileges in
            $this->loadDefaultRoles();
            $this->loadDefaultPrivileges();
        }

        //Now allow 'master' all access, except for the actions that have the nologin privilege (->the login action)
        if (!$this->_acl->hasRole('master')) {
            //Add role if not already present
            $this->_acl->addRole('master');
        }
        $this->_acl->allow('master');
        $this->_acl->deny('master', null, 'pr.nologin');
    }

    public function load() {
        if ($this->_cache instanceof Zend_Cache_Core) {
            $cache = $this->_cache;
            $id = $this->_cacheid;
            if (!($cache->test($id))) {
                // cache miss
                $this->build();
            } else {
                // cache hit
                $this->_acl = $cache->load($id);
            }
        } else {
            $this->build();
        }
    }

    /**
     * Recursively expands roles into Zend_Acl_Role objects
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
            throw new Exception("Possible circular reference detected while expanding role '{$roleName}'");
        }

        if (!empty($role['grl_parents'])) {
            $parents = explode(",", $role['grl_parents']);

            foreach ($parents as $parent) {
                $this->_expandRole($roleList, $parent, $depth + 1);
            }
        } else {
            $parents = array();
        }

        $this->addRole(new Zend_Acl_Role($role['grl_name']), $parents);

        $privileges = explode(",", $role['grl_privileges']);
        $this->addPrivilege($role['grl_name'], $privileges);

        $roleList[$roleName]['marked'] = true;
    }

    /**
     * Load access control list from db
     * @throws Exception
     */
    public function loadDbAcl() {
        $db = Zend_Registry::get('db');

        $sql = "SELECT grl_id_role,grl_name,grl_privileges,grl_parents FROM gems__roles";

        $roles = $db->fetchAll($sql);

        if (empty($roles)) {
            throw new Exception("No roles stored in db");
        }

        $roleList = array_combine(array_map(function($value) { return $value['grl_name']; }, $roles), $roles);

        foreach ($roleList as $role) {
            $this->_expandRole($roleList, $role['grl_name']);
        }

        return true;
    }

    public function loadDefaultPrivileges() {
        /**
         * Only add the nologin role, as the others should come from the database when it is initialized
         */
        $this->addPrivilege('nologin',
                        'pr.contact.bugs', 'pr.contact.support',
                        'pr.nologin'
                );
    }

    public function loadDefaultRoles() {
        /**
         * Only add the nologin role, as the others should come from the database when it is initialized
         */
        $this->addRole(new Zend_Acl_Role('nologin'));
    }

    private function save() {
        if ($this->_cache instanceof Zend_Cache_Core) {
            if (!$this->_cache->save($this->_acl, $this->_cacheid, array(), null))
                throw new Gems_Exception('Failed to save acl to cache');
        }
    }

    public function setCache($cache) {
        if ($cache instanceof Zend_Cache_Core) {
            $this->_cache = $cache;
        } elseif ($cache instanceof GemsEscort) {
            $this->_cache = $cache->cache;
        }
   }

}