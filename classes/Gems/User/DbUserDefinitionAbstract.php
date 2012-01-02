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
 * A standard, database stored user as of version 1.5.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class Gems_User_DbUserDefinitionAbstract extends Gems_User_UserDefinitionAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

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
            return $user->hasEmailAddress() && $user->canSetPassword();
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
     * Check whether a reset key is really linked to a user.
     *
     * @param Gems_User_User $user The user the key was created for (hopefully).
     * @param string The key
     * @return boolean
     */
    public function checkPasswordResetKey(Gems_User_User $user, $key)
    {
        $model = new MUtil_Model_TableModel('gems__user_passwords');

        $filter['gup_id_user'] = $user->getUserLoginId();
        $filter[] = 'DATE_ADD(gup_reset_requested, INTERVAL 24 HOUR) >= CURRENT_TIMESTAMP';

        $row = $model->loadFirst($filter);
        if ($row && $row['gup_reset_key']) {
            return $key == $row['gup_reset_key'];
        }

        return false;
    }

    public function getAuthAdapter($formValues)
    {
        $adapter = new Zend_Auth_Adapter_DbTable($this->db, 'gems__user_passwords', 'gul_login', 'gup_password');

        $pwd_hash = $this->hashPassword($formValues['password']);

        $select = $adapter->getDbSelect();
        $select->join('gems__user_logins', 'gup_id_user = gul_id_user', array())
               ->where('gul_can_login = 1')
               ->where('gul_id_organization = ?', $formValues['organization']);

        $adapter->setIdentity($formValues['userlogin'])
                ->setCredential($pwd_hash);

        return $adapter;
    }

    /**
     * Return a password reset key
     *
     * @param Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(Gems_User_User $user)
    {
        $model = new MUtil_Model_TableModel('gems__user_passwords');
        Gems_Model::setChangeFieldsByPrefix($model, 'gup', $user->getUserId());

        $data['gup_id_user'] = $user->getUserLoginId();

        $row = $model->loadFirst($data + array('DATE_ADD(gup_reset_requested, INTERVAL 24 HOUR) >= CURRENT_TIMESTAMP'));
        if ($row && $row['gup_reset_key']) {
            // Keep using the key.
            $data['gup_reset_key'] = $row['gup_reset_key'];
        } else {
            $data['gup_reset_key'] = $this->hashPassword(time() . $user->getEmailAddress());
        }
        $data['gup_reset_requested'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');

        $model->save($data);

        return $data['gup_reset_key'];
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

        $result = $this->db->fetchRow($select, array($login_name, $organization), Zend_Db::FETCH_ASSOC);

        /*
         * Handle the case that we have a login record, but no matching userdata (ie. user is inactive)
         * if you want some kind of auto-register you should change this
         */
        if ($result == false) {
            $result = array(
                'user_active'          => false,
                'user_role'            => 'nologin',
            );   
        }

        return $result;
    }

    /**
     * A select used by subclasses to add fields to the select.
     *
     * @param string $login_name
     * @param int $organization
     * @return Zend_Db_Select
     */
    abstract protected function getUserSelect($login_name, $organization);

    /**
     * Allow overruling of password hashing.
     *
     * @param string $password
     * @return string
     */
    protected function hashPassword($password)
    {
        return $this->project->getValueHash($password);
    }

    /**
     * Return true if the user has a password.
     *
     * @param Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(Gems_User_User $user)
    {
        $sql = "SELECT CASE WHEN gup_password IS NULL THEN 0 ELSE 1 END FROM gems__user_passwords WHERE gup_id_user = ?";

        return (boolean) $this->db->fetchOne($sql, $user->getUserLoginId());
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
        $data['gup_id_user']         = $user->getUserLoginId();
        $data['gup_reset_key']       = null;
        $data['gup_reset_requested'] = null;
        $data['gup_reset_required']  = 0;
        if (null === $password) {
            // Passwords may be emptied.
            $data['gup_password'] = null;
        } else {
            $data['gup_password'] = $this->hashPassword($password);
        }

        $model = new MUtil_Model_TableModel('gems__user_passwords');
        Gems_Model::setChangeFieldsByPrefix($model, 'gup', $user->getUserId());

        $model->save($data);

        return $this;
    }
}