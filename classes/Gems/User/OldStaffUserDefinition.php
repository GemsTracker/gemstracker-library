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
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Stub function for 1.4 style users. Tries to upgrade the user to
 * StaffUser at every opportunity.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_OldStaffUserDefinition extends Gems_User_UserDefinitionAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The time period in hours a reset key is valid for this definition.
     *
     * @var int
     */
    protected $hoursResetKeyIsValid = 24;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(Gems_User_User $user = null)
    {
        if ($user) {
            // Depends on the user.
            if ($user->hasEmailAddress() && $user->canSetPassword()) {
                $email = $user->getEmailAddress();
                if (empty($email)) {
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            return true;
        }
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(Gems_User_User $user = null)
    {
        return true;
    }

    /**
     * Returns an initialized Zend_Auth_Adapter_Interface
     *
     * @param Gems_User_User $user
     * @param string $password
     * @return Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter(Gems_User_User $user, $password)
    {
        $pwd_hash = $this->hashPassword($password);

        $sql = "SELECT gsf_id_user FROM gems__staff WHERE gsf_active = 1 AND gsf_login = ? AND gsf_id_organization = ? AND gsf_password = ?";

        if ($this->db->fetchOne($sql, array($user->getLoginName(), $user->getBaseOrganizationId(), $pwd_hash))) {
            $this->makeNewStaffUser($user, $password);

            return true;
        } else {
            return false;
        }

        /*
        $adapter = new Zend_Auth_Adapter_DbTable(null, 'gems__staff', 'gsf_login', 'gsf_password');

        $pwd_hash = $this->hashPassword($password);

        $select = $adapter->getDbSelect();
        $select->where('gsf_active = 1')
               ->where('gsf_id_organization = ?', $organizationId);

        $adapter->setIdentity($username)
                ->setCredential($pwd_hash);

        return $adapter;
        // */
    }

    /**
     * Return a password reset key
     *
     * @param Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(Gems_User_User $user)
    {
        $model = new MUtil_Model_TableModel('gems__staff');
        Gems_Model::setChangeFieldsByPrefix($model, 'gsf', $user->getUserId());

        $data['gsf_id_user'] = $user->getUserId();

        $row = $model->loadFirst($data + array('DATE_ADD(gsf_reset_req, INTERVAL ' . $this->hoursResetKeyIsValid . ' HOUR) >= CURRENT_TIMESTAMP'));
        if ($row && $row['gup_reset_key']) {
            // Keep using the key.
            $data['gsf_reset_key'] = $row['gsf_reset_key'];
        } else {
            $data['gsf_reset_key'] = $this->hashPassword(time() . $user->getEmailAddress());
        }
        $data['gsf_reset_req'] = new MUtil_Db_Expr_CurrentTimestamp();

        // Loop for case when hash is not unique
        while (true) {
            try {
                $model->save($data);

                // Old staff keys can by recognized because they start with 'os'
                return 'os' . $data['gsf_reset_key'];

            } catch (Zend_Db_Exception $zde) {
                $data['gsf_reset_key'] = $this->hashPassword(time() . $user->getEmailAddress());
            }
        }
    }

    /**
     * Returns a user object, that may be empty if the user is unknown.
     *
     * @param string $login_name
     * @param int $organization
     * @return array Of data to fill the user with.
     */
    public function getUserData($login_name, $organization)
    {
        $select = $this->getUserSelect($login_name, $organization);

        // For a multi-layout project we need to select the appropriate style too,
        // but as PATCHES may not be in effect we have to try two selects
        $select2 = clone $select;
        $select2->columns(array('user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges'), 'gems__groups');

        try {
            // Fails before patch has run...
            return $this->db->fetchRow($select2, array($login_name), Zend_Db::FETCH_ASSOC);

        } catch (Zend_Db_Exception $e) {
            // So then we try the old method
            return $this->db->fetchRow($select, array($login_name), Zend_Db::FETCH_ASSOC);
        }
    }

    /**
     * Stub to allow subclasses to add fields to the select.
     *
     * @param string $login_name
     * @param int $organization
     * @return Zend_Db_Select
     */
    protected function getUserSelect($login_name, $organization)
    {
        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility accross implementations.
         */
        $select = new Zend_Db_Select($this->db);
        $select->from('gems__staff', array(
                    'user_id'             => 'gsf_id_user',
                    'user_login'          => 'gsf_login',
                    'user_email'          => 'gsf_email',
                    'user_first_name'     => 'gsf_first_name',
                    'user_surname_prefix' => 'gsf_surname_prefix',
                    'user_last_name'      => 'gsf_last_name',
                    'user_gender'         => 'gsf_gender',
                    'user_group'          => 'gsf_id_primary_group',
                    'user_locale'         => 'gsf_iso_lang',
                    'user_logout'         => 'gsf_logout_on_survey',
                    'user_base_org_id'    => 'gsf_id_organization',
                    'user_resetkey_valid' => 'CASE WHEN DATE_ADD(gsf_reset_req, INTERVAL ' . $this->hoursResetKeyIsValid . ' HOUR) >= CURRENT_TIMESTAMP THEN 1 ELSE 0 END',
                    ))
               ->join('gems__groups', 'gsf_id_primary_group = ggp_id_group', array(
                   'user_role'            => 'ggp_role',
                   ))
               ->where('ggp_group_active = 1')
               ->where('gsf_active = 1')
               ->where('gsf_login = ?')
               ->limit(1);

        return $select;
    }

    /**
     * Allow overruling of password hashing.
     *
     * @param string $password
     * @return string
     */
    protected function hashNewPassword($password)
    {
        return $this->project->getValueHash($password);
    }

    /**
     * Allow overruling of password hashing.
     *
     * @param string $password
     * @return string
     */
    protected function hashPassword($password)
    {
        return md5($password);
    }

    /**
     * Sets the user up as a new staff user
     *
     * @param Gems_User_User $user
     * @param string $password
     */
    protected function makeNewStaffUser(Gems_User_User $user, $password)
    {
        $staff_id = $user->getUserId();
        $sql      = 'SELECT gul_id_user FROM gems__user_logins WHERE gul_can_login = 1 AND gul_login = ? AND gul_id_organization = ?';

        try {
            $user_id = $this->db->fetchOne($sql, array($user->getLoginName(), $user->getBaseOrganizationId()));

            $currentTimestamp = new MUtil_Db_Expr_CurrentTimestamp();

            // Move to USER_STAFF
            $values['gup_id_user']         = $user_id;
            $values['gup_password']        = $this->hashNewPassword($password);
            $values['gup_reset_key']       = null;
            $values['gup_reset_requested'] = null;
            $values['gup_reset_required']  = 0;
            $values['gup_changed']         = $currentTimestamp ;
            $values['gup_changed_by']      = $staff_id;
            $values['gup_created']         = $currentTimestamp ;
            $values['gup_created_by']      = $staff_id;

            $this->db->insert('gems__user_passwords', $values);

            // Update user class
            $values = array();
            $values['gul_user_class']    = Gems_User_UserLoader::USER_STAFF;
            $values['gul_changed']       = $currentTimestamp ;
            $values['gul_changed_by']    = $staff_id;
            $this->db->update('gems__user_logins', $values, $this->db->quoteInto('gul_id_user = ?', $user_id));

            // Remove old password
            $values = array();
            $values['gsf_password']   = null;
            $values['gsf_changed']    = $currentTimestamp ;
            $values['gsf_changed_by'] = $user_id;

            $this->db->update('gems__staff', $values, $this->db->quoteInto('gsf_id_user = ?', $staff_id));

            $user->refresh(Gems_User_UserLoader::USER_STAFF);

        } catch (Zend_Db_Exception $e) {
            GemsEscort::getInstance()->logger->log($e->getMessage(), Zend_Log::ERR);
            // Fall through as this does not work if the database upgrade did not run
            // MUtil_Echo::r($e);

        }
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param Gems_User_User $user The user whose password to change
     * @param string $password
     * @return Gems_User_UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(Gems_User_User $user, $password)
    {
        $this->makeNewStaffUser($user, $password);

        return $this;
    }
}