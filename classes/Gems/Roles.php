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
 */

/**
 * This is the generic Roles class to be extended by the project
 *
 * It loads the ACL in four stages:
 *
 * 1. $this->loadDefaultRoles()
 * 2. $this->loadDefaultPrivileges()
 * Normally you should not touch this to make upgrading easier
 *
 * 3. $this->loadProjectRoles()
 * 4. $this->loadProjectPrivileges()
 * This is where you can revoke or add privileges and/or add your own roles.
 *
 *
 * @version $Id: Roles.php 345 2011-07-28 08:39:24Z 175780 $
 * @author user
 * @filesource
 * @package Gems
 * @subpackage Roles
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

//Reset de ACL en bouw opnieuw op
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

            //Voeg project rollen em privileges in
            $this->loadProjectRoles();
            $this->loadProjectPrivileges();
        }
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
        $this->addPrivilege('nologin',
                        'pr.contact.bugs', 'pr.contact.support',
                        'pr.nologin'
                )
                ->addPrivilege('guest',
                        'pr.ask',
                        'pr.contact.bugs', 'pr.contact.support',
                        'pr.islogin',
                        'pr.respondent'
                )
                // ->allow('respondent', null, array('islogin'))
                ->addPrivilege('staff',
                        'pr.option.edit', 'pr.option.password',
                        'pr.plan', 'pr.plan.overview', 'pr.plan.token',
                        'pr.project', 'pr.project.questions',
                        'pr.respondent.create', 'pr.respondent.edit',
                        'pr.respondent.who', //Who filled out the survey instead of just the role
                        'pr.setup',
                        'pr.staff',
                        'pr.survey', 'pr.survey.create',
                        'pr.token', 'pr.token.answers', 'pr.token.delete', 'pr.token.edit', 'pr.token.mail', 'pr.token.print',
                        'pr.track', 'pr.track.create', 'pr.track.delete', 'pr.track.edit'
                )
                ->addPrivilege('researcher',
                        'pr.invitation',
                        'pr.result',
                        'pr.islogin'
                )
                // ->allow('security',   null, array())
                ->addPrivilege('admin',
                        'pr.consent', 'pr.consent.create', 'pr.consent.edit',
                        'pr.group',
                        'pr.role',
                        'pr.mail', 'pr.mail.create', 'pr.mail.delete', 'pr.mail.edit',
                        'pr.organization', 'pr.organization-switch',
                        'pr.plan.overview.excel', 'pr.plan.respondent', 'pr.plan.respondent.excel', 'pr.plan.token.excel',
                        'pr.project-information',
                        'pr.reception', 'pr.reception.create', 'pr.reception.edit',
                        'pr.respondent.choose-org', 'pr.respondent.delete',
                        'pr.respondent.result', //Show the result of the survey in the overview
                        'pr.source',
                        'pr.staff.create', 'pr.staff.delete', 'pr.staff.edit',
                        'pr.survey-maintenance',
                        'pr.track-maintenance',
                        'pr.token.mail.freetext'
                )
                ->addPrivilege('super',
                        'pr.consent.delete',
                        'pr.country', 'pr.country.create', 'pr.country.delete', 'pr.country.edit',
                        'pr.database', 'pr.database.create', 'pr.database.delete', 'pr.database.edit', 'pr.database.execute', 'pr.database.patches',
                        'pr.group.create', 'pr.group.edit',
                        'pr.role.create', 'pr.role.edit',
                        'pr.language',
                        'pr.organization.create', 'pr.organization.edit',
                        'pr.plan.choose-org', 'pr.plan.mail-as-application',
                        'pr.reception.delete',
                        'pr.source.create', 'pr.source.edit',
                        'pr.staff.edit.all',
                        'pr.survey-maintenance.edit',
                        'pr.track-maintenance.create', 'pr.track-maintenance.edit'
        );

        /*         * ***************************************
         * UNASSIGNED RIGHTS (by default)
         *
         * 'pr.group.delete'
         * 'pr.organization.delete'
         * 'pr.source.delete'
         * 'pr.track-maintenance.delete'
         * *************************************** */
    }

    public function loadDefaultRoles() {
        $this->addRole(new Zend_Acl_Role('nologin'))
                ->addRole(new Zend_Acl_Role('guest'))
                ->addRole(new Zend_Acl_Role('respondent'), 'guest')
                ->addRole(new Zend_Acl_Role('staff'), 'guest')
                ->addRole(new Zend_Acl_Role('physician'), 'staff')
                ->addRole(new Zend_Acl_Role('researcher'))
                ->addRole(new Zend_Acl_Role('security'), 'guest')
                ->addRole(new Zend_Acl_Role('admin'), array('staff', 'researcher', 'security'))
                ->addRole(new Zend_Acl_Role('super'), 'admin');
    }

    public function loadProjectPrivileges() {

    }

    public function loadProjectRoles() {

    }

    private function save() {
        if ($this->_cache instanceof Zend_Cache_Core) {
            if (!$this->_cache->save($this->_acl, $this->_cacheid, array(), null))
                echo "MISLUKT!";
        }
    }

    public function setCache($cache) {
        if ($cache instanceof Zend_Cache_Core)
            $this->_cache = $cache;
    }

}