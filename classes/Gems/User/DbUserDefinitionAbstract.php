<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

/**
 * A standard, database stored user as of version 1.5.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class Gems_User_DbUserDefinitionAbstract extends \Gems_User_UserDefinitionAbstract
{
    /**
     * If passwords also need to be checked with the old hash method
     * @var boolean
     */
    public $checkOldHashes = true;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Laminas\Db\Adapter\Adapter
     */
    protected $db2;

    /**
     *
     * @var  int The hash algorithm used for password_hash
     */
    protected $hashAlgorithm = PASSWORD_DEFAULT;

    /**
     * The time period in hours a reset key is valid for this definition.
     *
     * @var int
     */
    protected $hoursResetKeyIsValid = 24;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(\Gems_User_User $user = null)
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
     *
     * @return boolean When there is space to save the new hash
     */
    public function canSaveNewHash()
    {
        $model = new \MUtil_Model_TableModel('gems__user_passwords');

        // Can save if storage is long enough
        return $model->get('gup_password', 'maxlength') >= 255;
    }

    /**
     * Return true if the two factor can be set.
     *
     * @return boolean
     */
    public function canSaveTwoFactorKey()
    {
        return true;
    }

   /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(\Gems_User_User $user = null)
    {
        return true;
    }

    /**
     * Checks if the current users hashed password uses the current hash algorithm.
     * If not it rehashes and saves the current password
     * @param  \Gems_User_User  $user     Current logged in user
     * @param  integer          $password Raw password
     * @return boolean          password has been rehashed
     */
    public function checkRehash(\Gems_User_User $user, $password)
    {
        if (! $this->canSaveNewHash()) {
            return false;
        }

        $model = new \MUtil_Model_TableModel('gems__user_passwords');
        $row   = $model->loadFirst(['gup_id_user' => $user->getUserLoginId()]);

        if ($row && password_needs_rehash($row['gup_password'], $this->getHashAlgorithm(), $this->getHashOptions())) {
            $data['gup_id_user']  = $user->getUserLoginId();
            $data['gup_password'] = $this->hashPassword($password);

            $model = new \MUtil_Model_TableModel('gems__user_passwords');
            \Gems_Model::setChangeFieldsByPrefix($model, 'gup', $user->getUserId());

            $model->save($data);

            return true;
        }

        return false;
    }

    /**
     * Returns an initialized Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems_User_User $user
     * @param string $password
     * @return Laminas\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems_User_User $user, $password)
    {
        $db2 = $this->getDb2();

        $credentialValidationCallback = $this->getCredentialValidationCallback();

        $adapter = new CallbackCheckAdapter($db2, 'gems__user_passwords', 'gul_login', 'gup_password', $credentialValidationCallback);

        $select = $adapter->getDbSelect();
        $select->join('gems__user_logins', 'gup_id_user = gul_id_user', array())
               ->where('gul_can_login = 1')
               ->where(array('gul_id_organization ' => $user->getBaseOrganizationId()));

        $adapter->setIdentity($user->getLoginName())
                ->setCredential($password);

        return $adapter;
    }

    /**
     * get the credential validation callback function for the callback check adapter
     * @return callback Function
     */
    public function getCredentialValidationCallback()
    {
        if ($this->checkOldHashes) {
            $credentialValidationCallback = function($dbCredential, $requestCredential) {
                if (password_verify($requestCredential, $dbCredential)) {
                    return true;
                } elseif ($dbCredential == $this->hashOldPassword($requestCredential)) {
                    return true;
                }

                return false;
            };
        } else {
            $credentialValidationCallback = function($dbCredential, $requestCredential) {
                if (password_verify($requestCredential, $dbCredential)) {
                    return true;
                }

                return false;
            };
        }

        return $credentialValidationCallback;
    }

    /**
     * Create a Zend DB 2 Adapter needed for the Laminas\Authentication library
     * @return Laminas\Db\Adapter\Adapter Zend Db Adapter
     */
    protected function getDb2()
    {
        if (!$this->db2 instanceof Adapter) {
            $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            $resources = $config->getOption('resources');
            $dbConfig = array(
                'driver'   => $resources['db']['adapter'],
                'hostname' => $resources['db']['params']['host'],
                'database' => $resources['db']['params']['dbname'],
                'username' => $resources['db']['params']['username'],
                'password' => $resources['db']['params']['password'],
                'charset'  => $resources['db']['params']['charset'],
            );
            if (isset($resources['db']['params']['port'])) {
                $dbConfig['port'] = $resources['db']['params']['port'];
            }

            $this->db2 = new Adapter($dbConfig);
        }

        return $this->db2;
    }

    /**
     * Get the current password hash algorithm
     * Currently defaults to BCRYPT in PHP 5.5-7.2
     *
     * @return string
     */
    public function getHashAlgorithm()
    {
        return $this->hashAlgorithm;
    }

    /**
     * Get the current hash options
     * Default:
     * cost => 10  // higher numbers will make the hash slower but stronger.
     *
     * @return array Current password hash options
     */
    public function getHashOptions()
    {
        return [];
    }

    /**
     * Return a password reset key
     *
     * @param \Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems_User_User $user)
    {
        $model = new \MUtil_Model_TableModel('gems__user_passwords');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gup', $user->getUserId());

        $data['gup_id_user'] = $user->getUserLoginId();
        $filter = $data;

        if ((0 == ($this->hoursResetKeyIsValid % 24)) || \Zend_Session::$_unitTestEnabled) {
            $filter[] = 'ADDDATE(gup_reset_requested, ' . intval($this->hoursResetKeyIsValid / 24) . ') >= CURRENT_TIMESTAMP';
        } else {
            $filter[] = 'DATE_ADD(gup_reset_requested, INTERVAL ' . $this->hoursResetKeyIsValid . ' HOUR) >= CURRENT_TIMESTAMP';
        }
        $row = $model->loadFirst($filter);
        if ($row && $row['gup_reset_key']) {
            // Keep using the key.
            $data['gup_reset_key'] = $row['gup_reset_key'];
        } else {
            $data['gup_reset_key'] = hash('sha256', time() . $user->getEmailAddress());
        }
        $data['gup_reset_requested'] = new \MUtil_Db_Expr_CurrentTimestamp();

        // Loop for case when hash is not unique
        while (true) {
            try {
                $model->save($data);

                return $data['gup_reset_key'];

            } catch (\Zend_Db_Exception $zde) {
                $data['gup_reset_key'] = hash('sha256', time() . $user->getEmailAddress());
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

        try {
            $result = $this->db->fetchRow($select, array($login_name, $organization), \Zend_Db::FETCH_ASSOC);

        } catch (\Zend_Db_Statement_Exception $e) {
            // \MUtil_Echo::track($e->getMessage());
            // \MUtil_Echo::track($select->__toString());

            // Yeah ugly. Can be removed when all projects have been patched to 1.8.4
            $sql = $select->__toString();
            $sql = str_replace([
                '`gems__user_logins`.`gul_two_factor_key`',
                '`gems__user_logins`.`gul_enable_2factor`',
                '`gems__staff`.`gsf_is_embedded`',
                ], 'NULL', $sql);
            // \MUtil_Echo::track($sql);
            try {
                // Next try
                $result = $this->db->fetchRow($sql, array($login_name, $organization), \Zend_Db::FETCH_ASSOC);

            } catch (\Zend_Db_Statement_Exception $e) {
                // Can be removed when all projects have been patched to 1.6.2
                $sql = str_replace('gup_last_pwd_change', 'gup_changed', $sql);

                // New user login fields in 1.9.1
                $sql = str_replace(['gul_otp_count', 'gul_otp_requested'], 'gul_changed', $sql);


                // Last try
                $result = $this->db->fetchRow($sql, array($login_name, $organization), \Zend_Db::FETCH_ASSOC);
            }
        }


        /*
         * Handle the case that we have a login record, but no matching userdata (ie. user is inactive)
         * if you want some kind of auto-register you should change this
         */
        if ($result == false) {
            $result = \Gems_User_NoLoginDefinition::getNoLoginDataFor($login_name, $organization);
        }

        return $result;
    }

    /**
     * A select used by subclasses to add fields to the select.
     *
     * @param string $login_name
     * @param int $organization
     * @return \Zend_Db_Select
     */
    abstract protected function getUserSelect($login_name, $organization);

    /**
     * Allow overruling of password hashing.
     *
     * @param string $password
     * @return string
     */
    public function hashPassword($password)
    {
        return password_hash($password, $this->getHashAlgorithm(), $this->getHashOptions());
    }

    /**
     * Allow overruling of password hashing.
     *
     * @param string $password
     * @return string
     */
    public function hashOldPassword($password)
    {
        return $this->project->getValueHash($password);
    }

    /**
     * Return true if the user has a password.
     *
     * @param \Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems_User_User $user)
    {
        $sql = "SELECT CASE WHEN gup_password IS NULL THEN 0 ELSE 1 END FROM gems__user_passwords WHERE gup_id_user = ?";

        return (boolean) $this->db->fetchOne($sql, $user->getUserLoginId());
    }

    /**
     * Set the Laminas\Db\Adapter\Adapter manually
     * @param Laminas\Db\Adapter\Adapter $adapter [description]
     */
    public function setDb2(Adapter $adapter)
    {
        $this->db2 = $adapter;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param \Gems_User_User $user The user whose password to change
     * @param string $password
     * @return \Gems_User_UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(\Gems_User_User $user, $password)
    {
        $data['gup_id_user']         = $user->getUserLoginId();
        $data['gup_reset_key']       = null;
        $data['gup_reset_requested'] = null;
        $data['gup_reset_required']  = 0;
        if (null === $password) {
            // Passwords may be emptied.
            $data['gup_password'] = null;
        } elseif ($this->canSaveNewHash()) {
            $data['gup_password'] = $this->hashPassword($password);
        } else {
            $data['gup_password'] = $this->hashOldPassword($password);
        }
        $data['gup_last_pwd_change'] = new \Zend_Db_Expr('CURRENT_TIMESTAMP');

        $model = new \MUtil_Model_TableModel('gems__user_passwords');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gup', $user->getUserId());

        $model->save($data);

        return $this;
    }

    /**
     *
     * @param \Gems_User_User $user The user whose key to set
     * @param string $newKey
     * @param boolean $enabled Optional, only set when not null
     * @return $this
     */
    public function setTwoFactorKey(\Gems_User_User $user, $newKey, $enabled = null)
    {
        $data['gul_id_user']        = $user->getUserLoginId();
        $data['gul_two_factor_key'] = $newKey;

        if (null !== $enabled) {
            $data['gul_enable_2factor'] = $enabled ? 1 : 0;
        }

        $model = new \MUtil_Model_TableModel('gems__user_logins');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gul', $user->getUserId());

        $model->save($data);

        return $this;
    }
}
