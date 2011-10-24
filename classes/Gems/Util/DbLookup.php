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
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Lookup global information from the database, allowing for project specific overrides
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Util_DbLookup extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var Zend_Acl
     */
    protected $acl;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @var Zend_Session
     */
    protected $session;

    public function getActiveOrganizations()
    {
        static $organizations;

        if (! $organizations) {
            $orgId = GemsEscort::getInstance()->getCurrentOrganization();
            $organizations = $this->db->fetchPairs('
                SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE (gor_active=1 AND
                            gor_id_organization IN (SELECT gr2o_id_organization FROM gems__respondent2org)) OR
                        gor_id_organization = ?
                    ORDER BY gor_name', $orgId);
        }

        return $organizations;
    }

    /**
     * Return key/value pairs of all active staff members
     *
     * @staticvar array $data
     * @return array
     */
    public function getActiveStaff()
    {
        static $data;

        if (! $data) {
            $data = $this->db->fetchPairs("SELECT gsf_id_user, CONCAT(COALESCE(gsf_last_name, '-'), ', ', COALESCE(gsf_first_name, ''), COALESCE(CONCAT(' ', gsf_surname_prefix), ''))
                    FROM gems__staff WHERE gsf_active = 1 ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix");
        }

        return $data;
    }

    public function getActiveStaffGroups()
    {
        static $groups;

        if (! $groups) {
            $groups = $this->db->fetchPairs('SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
        }

        return $groups;
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he inherits rights from
     *
     * @return array
     */
    public function getAllowedStaffGroups()
    {
        $groups = $this->getActiveStaffGroups();
        if ($this->session->user_role === 'super') {
            return $groups;

        } else {
            $rolesAllowed = $this->acl->getRoleAndParents($this->session->user_role);
            $roles        = $this->db->fetchPairs('SELECT ggp_id_group, ggp_role FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
            $result       = array();

            foreach ($roles as $id => $role) {
                if ((in_array($role, $rolesAllowed)) && isset($groups[$id])) {
                    $result[$id] = $groups[$id];
                }
            }

            return $result;
        }
    }

    public function getDefaultGroup()
    {
        $groups  = $this->getActiveStaffGroups();
        $roles   = $this->db->fetchPairs('SELECT ggp_role, ggp_id_group FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
        $current = null;

        foreach (array_reverse($this->acl->getRoles()) as $roleId) {
            if (isset($roles[$roleId], $groups[$roles[$roleId]])) {
                if ($current) {
                    if ($this->acl->inheritsRole($current, $roleId)) {
                        $current = $roleId;
                    }
                } else {
                    $current = $roleId;
                }
            }
        }

        if (isset($roles[$current])) {
            return $roles[$current];
        }
    }

    public function getGroups()
    {
        static $groups;

        if (! $groups) {
            $groups = $this->util->getTranslated()->getEmptyDropdownArray() +
                $this->db->fetchPairs('SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_group_active=1 ORDER BY ggp_name');
        }

        return $groups;
    }

    /**
     * Return the available mail templates.
     *
     * @staticvar array $data
     * @return array The tempalteId => subject list
     */
    public function getMailTemplates()
    {
        static $data;

        if (! $data) {
            $data = $this->db->fetchPairs("SELECT gmt_id_message, gmt_subject FROM gems__mail_templates ORDER BY gmt_subject");
        }

        return $data;
    }

    /**
     *
     * @staticvar array $organizations
     * @return array List of the active organizations
     */
    public function getOrganizations()
    {
        static $organizations;

        if (! $organizations) {
            $organizations = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 ORDER BY gor_name');
            natsort($organizations);
        }

        return $organizations;
    }

    public function getRoles()
    {
        $roles = array();

        if ($this->acl) {
            foreach ($this->acl->getRoles() as $role) {
                $roles[$role] = $this->translate->_(ucfirst($role));
            }
        }

        return $roles;
    }

    /**
     * Return key/value pairs of all staff members, currently active or not
     *
     * @staticvar array $data
     * @return array
     */
    public function getStaff()
    {
        static $data;

        if (! $data) {
            $data = $this->db->fetchPairs("SELECT gsf_id_user, CONCAT(COALESCE(gsf_last_name, '-'), ', ', COALESCE(gsf_first_name, ''), COALESCE(CONCAT(' ', gsf_surname_prefix), ''))
                    FROM gems__staff WHERE ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix");
        }

        return $data;
    }

    public function getStaffGroups()
    {
        static $groups;

        if (! $groups) {
            $groups = $this->db->fetchPairs('SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_staff_members=1 ORDER BY ggp_name');
        }

        return $groups;
    }

    public function getUserConsents()
    {
        static $consents;

        if (! $consents) {
            $consents = $this->db->fetchPairs('SELECT gco_description, gco_description FROM gems__consents ORDER BY gco_order');

            foreach ($consents as &$name) {
                $name = $this->translate->_($name);
            }
        }

        return $consents;
    }
}
