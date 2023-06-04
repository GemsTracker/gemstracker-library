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

use Gems\Repository\OrganizationRepository;

/**
 * The user used when GemsTracker is called form the console
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ConsoleUserDefinition extends \Gems\User\UserDefinitionAbstract
{

    /**
     * @var OrganizationRepository
     */
    protected $organizationRepository;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * Returns an initialized \Zend_Auth_Adapter_Interface
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return \Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter(\Gems\User\User $user, $password)
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
            $orgs = $this->organizationRepository->getOrganizations();
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
            'user_id'                => \Gems\User\UserLoader::CONSOLE_USER_ID,
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