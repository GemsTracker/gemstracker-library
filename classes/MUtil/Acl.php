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
 * @package    MUtil
 * @subpackage Acl
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends Zend_Acl with a couple of overview functions
 *
 * @package    MUtil
 * @subpackage Acl
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Acl extends Zend_Acl
{
    const PARENTS   = 'PARENTS';
    const INHERITED = 'INHERITED';

    /**
     * Adds an "allow" rule to the ACL
     *
     * @param  Zend_Acl_Role_Interface|string|array     $roles
     * @param  string|array                             $privileges
     * @uses   Zend_Acl::allow()
     * @return Zend_Acl Provides a fluent interface
     */
    public function addPrivilege($roles, $privileges_args)
    {
        $privileges = MUtil_Ra::args(func_get_args(), 1);

        return $this->allow($roles, null, $privileges);
    }

    public function echoRules()
    {
        MUtil_Echo::r($this->_rules);
    }

    public function getPrivilegeRoles()
    {
        $results = array();

        if (isset($this->_rules['allResources']['byRoleId'])) {
            foreach ($this->_rules['allResources']['byRoleId'] as $role => $rule) {
                if (isset($rule['byPrivilegeId'])) {

                    foreach ($rule['byPrivilegeId'] as $privilege => $pdata) {
                        if (! isset($results[$privilege])) {
                            $results[$privilege] = array(
                                parent::TYPE_ALLOW => array(),
                                parent::TYPE_DENY  => array());
                        }

                        if (isset($pdata['type'])) {
                            if ($pdata['type'] === parent::TYPE_ALLOW) {
                                $results[$privilege][parent::TYPE_ALLOW][] = $role;
                            } elseif ($pdata['type'] === parent::TYPE_DENY) {
                                $results[$privilege][parent::TYPE_DENY][]  = $role;
                            }
                        }
                    }
                }
                // MUtil_Echo::r($results);
            }
        }

        return $results;
    }

    /**
     * Retrieve an array of the current role and all parents
     *
     * @param string $role
     * @param array $parents
     * @return array With identical keys and values roleId => roleId
     */
    public function getRoleAndParents($role, $parents = array()) {
        $results = $parents;
        $result = $this->_getRoleRegistry()->getParents($role);
        foreach($result as $roleId => $selRole) {
            if (!in_array($roleId, $results)) {
                $results = $this->getRoleAndParents($roleId, $results);
            }
            $results[$roleId] = $roleId;
        }
        $results[$role] = $role;
        return $results;
    }

    /**
     * Returns an array of roles with all direct and inherited privileges
     *
     * Sample output:
     * <code>
     *   [MUtil_Acl::PARENTS]=>array(parent_name=>parent_object),
     *   [Zend_Acl::TYPE_ALLOW]=>array([index]=>privilege),
     *   [Zend_Acl::TYPE_DENY]=>array([index]=>privilege),
     *   [MUtil_Acl::INHERITED]=>array([Zend_Acl::TYPE_ALLOW]=>array([index]=>privilege),
     *                                 [Zend_Acl::TYPE_DENY]=>array([index]=>privilege))
     * </code>
     *
     * @return array
     */
    public function getRolePrivileges()
    {
        $results = array();

        foreach ($this->getRoles() as $role) {
            $rules = $this->getPrivileges($role);
            $results[$role] = array(
                self::PARENTS => $this->_getRoleRegistry()->getParents($role),
                parent::TYPE_ALLOW => $rules[parent::TYPE_ALLOW],
                parent::TYPE_DENY => $rules[parent::TYPE_DENY]);

            //Haal overerfde rollen op
            if (is_array($results[$role][self::PARENTS])) {
                $role_inherited_allowed = array();
                $role_inherited_denied = array();
                foreach ($results[$role][self::PARENTS] as $parent_name => $parent) {
                    $parent_allowed = $results[$parent_name][parent::TYPE_ALLOW];
                    $parent_denied = $results[$parent_name][parent::TYPE_DENY];
                    $parent_inherited_allowed = $results[$parent_name][self::INHERITED][parent::TYPE_ALLOW];
                    $parent_inherited_denied = $results[$parent_name][self::INHERITED][parent::TYPE_DENY];
                    $role_inherited_allowed = array_merge($role_inherited_allowed, $parent_allowed, $parent_inherited_allowed);
                    $role_inherited_denied = array_merge($role_inherited_denied, $parent_denied, $parent_inherited_denied);
                }
                $results[$role][self::INHERITED][parent::TYPE_ALLOW] = array_unique($role_inherited_allowed);
                $results[$role][self::INHERITED][parent::TYPE_DENY] = array_unique($role_inherited_denied);
            }
        }

        return $results;
    }

    /**
     * Returns all allow and deny rules for a given role
     *
     * Sample output:
     * <code>
     *   [Zend_Acl::TYPE_ALLOW]=>array(<index>=>privilege),
     *   [Zend_Acl::TYPE_DENY]=>array(<index>=>privilege)
     * </code>
     *
     * @param string $role
     * @return array
     */
    public function getPrivileges ($role) {
        $rule = $this->_getRules(null, $this->getRole($role));

        $results = array(parent::TYPE_ALLOW => array(),
                         parent::TYPE_DENY  => array());

        if (isset($rule['byPrivilegeId'])) {
            foreach ($rule['byPrivilegeId'] as $privilege => $pdata) {
                if (isset($pdata['type'])) {
                    if ($pdata['type'] === parent::TYPE_ALLOW) {
                        $results[parent::TYPE_ALLOW][] = $privilege;
                    } elseif ($pdata['type'] === parent::TYPE_DENY) {
                        $results[parent::TYPE_DENY][]  = $privilege;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Removes a previously set "allow" rule from the ACL
     *
     * @param  Zend_Acl_Role_Interface|string|array     $roles
     * @param  string|array                             $privileges
     * @uses   Zend_Acl::allow()
     * @return Zend_Acl Provides a fluent interface
     */
    public function removePrivilege($roles, $privileges_args)
    {
        $privileges = MUtil_Ra::args(func_get_args(), 1);

        return $this->removeAllow($roles, null, $privileges);
    }
}
