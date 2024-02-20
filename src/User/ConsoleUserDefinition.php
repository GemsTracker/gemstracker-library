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

use Gems\Project\ProjectSettings;
use Gems\Repository\OrganizationRepository;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Adapter\Callback;
use Laminas\Db\Exception\RuntimeException;

/**
 * The user used when GemsTracker is called form the console
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ConsoleUserDefinition extends UserDefinitionAbstract
{
    public function __construct(
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ProjectSettings $project,
    )
    {
    }

    /**
     * Returns an initialized \Zend_Auth_Adapter_Interface
     *
     * @param User $user
     * @param string $password
     * @return AdapterInterface
     */
    public function getAuthAdapter(User $user, string $password): AdapterInterface
    {
        $project = $this->project;
        return new Callback(function() use ($project) {
            return (bool)$project->getConsoleRole();
        });
    }

    /**
     * Returns the data for a user object. It may be empty if the user is unknown.
     *
     * @param string $loginName
     * @param int $organizationId
     * @return array Of data to fill the user with.
     */
    public function getUserData(string $loginName, int $organizationId): array
    {
        $orgs = null;

        try {
            $orgs = $this->organizationRepository->getOrganizations();
        } catch (RuntimeException $zde) {
        }
        if (! $organizationId) {
            if ($orgs) {
                // Set to first made active organization
                $organizationId = min(array_keys($orgs));
            } else {
                $organizationId = 0;
            }
        }
        if (! $orgs) {
            // Table might not exist or be empty, so do something failsafe
            $orgs = [$organizationId => 'create db first'];
        }

        return [
            'user_id'                => UserLoader::CONSOLE_USER_ID,
            'user_login'             => $loginName,
            'user_name'              => $loginName,
            'user_group'             => 800,
            'user_role'              => $this->project->getConsoleRole(),
            'user_style'             => 'gems',
            'user_base_org_id'       => $organizationId,
            'user_allowed_ip_ranges' => null,
            'user_blockable'         => false,
            '__allowedOrgs'          => $orgs
        ];
    }
}