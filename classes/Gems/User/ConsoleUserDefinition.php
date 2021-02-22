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
 * The user used when GemsTracker is called form the console
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_ConsoleUserDefinition extends \Gems_User_UserDefinitionAbstract
{
    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Returns an initialized \Zend_Auth_Adapter_Interface
     *
     * @param \Gems_User_User $user
     * @param string $password
     * @return \Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter(\Gems_User_User $user, $password)
    {
        return (boolean) $this->project->getConsoleRole();
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
        $orgs = null;

        try {
            $orgs = $this->db->fetchPairs("SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active = 1 ORDER BY gor_name");
            natsort($orgs);
        } catch (\Zend_Db_Exception $zde) {
        }
        if (! $organization) {
            if ($orgs) {
                // Set to first made active organization
                $organization = min(array_keys($orgs));
            } else {
                $organization = 0;
            }
        }
        if (! $orgs) {
            // Table might not exist or be empty, so do something failsafe
            $orgs = array($organization => 'create db first');
        }

        return array(
            'user_id'                => \Gems_User_UserLoader::SYSTEM_USER_ID,
            'user_login'             => $login_name,
            'user_name'              => $login_name,
            'user_group'             => 800,
            'user_role'              => $this->project->getConsoleRole(),
            'user_style'             => 'gems',
            'user_base_org_id'       => $organization,
            'user_allowed_ip_ranges' => null,
            'user_blockable'         => false,
            '__allowedOrgs'          => $orgs
            );
    }
}