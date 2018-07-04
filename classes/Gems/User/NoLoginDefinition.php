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

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_NoLoginDefinition extends \Gems_User_UserDefinitionAbstract
{
    /**
     * Returns an initialized Zend\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems_User_User $user
     * @param string $password
     * @return Zend\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems_User_User $user, $password)
    {
        return false;
    }

    /**
     * Returns the data for a no login user object.
     *
     * @param string $loginName
     * @param int $organization
     * @return array Of data to fill the user with.
     */
    public static function getNoLoginDataFor($loginName, $organization)
    {
        return array(
            'user_id'             => 0,
            'user_login'          => $loginName,
            'user_name'           => $loginName,
            'user_base_org_id'    => $organization,
            'user_active'         => false,
            'user_role'           => 'nologin',
            );
    }

    /**
     * Returns the data for a user object. It may be empty if the user is unknown.
     *
     * @param string $login_name
     * @param int $organization
     * @return array Of data to fill the user with.
     */
    public function getUserData($login_name, $organization)
    {
        return self::getNoLoginDataFor($login_name, $organization);
    }

    /**
     * Returns true when users using this definition are staff members.
     *
     * Used only when the definition does not return a user_staff field.
     *
     * @return boolean
     */
    public function isStaff()
    {
        return false;
    }
}
