<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

use Gems\Exception\AuthenticationException;

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class NoLoginDefinition extends \Gems\User\UserDefinitionAbstract
{
    /**
     * Returns an initialized \Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return \Laminas\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems\User\User $user, $password)
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
        throw new AuthenticationException('no login data');
        /*return array(
            'user_id'             => 0,
            'user_login'          => $loginName,
            'user_name'           => $loginName,
            'user_base_org_id'    => $organization,
            'user_active'         => false,
            'user_role'           => 'nologin',
            'user_embedded'       => false,
            );*/
    }

    /**
     * Returns the data for a user object. It may be empty if the user is unknown.
     *
     * @param string $login_name
     * @param int $organization
     * @return array Of data to fill the user with.
     */
    public function getUserData($login_name, $organization): array
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