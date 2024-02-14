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

use Gems\Auth\Adapter\Callback;
use Gems\Db\ResultFetcher;
use Gems\Project\ProjectSettings;
use Gems\Util;
use Laminas\Authentication\Adapter\AdapterInterface;

/**
 * The user defined in the project.ini by admin.user and admin.pwd.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ProjectUserDefinition extends UserDefinitionAbstract
{
    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        protected readonly ProjectSettings $project,
        protected readonly Util $util,
    )
    {
    }

    /**
     * Returns an initialized \Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return \Laminas\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(User $user, string $password): AdapterInterface
    {
        $adapter = new Callback([$this->project, 'checkSuperAdminPassword'], $user->getLoginName(), array($password));
        return $adapter;
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
            $orgs = $this->resultFetcher->fetchPairs("SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active = 1 ORDER BY gor_name");
            natsort($orgs);
        } catch (\Zend_Db_Exception $zde) {
        }
        if (! $orgs) {
            // Table might not exist or be empty, so do something failsafe
            $orgs = UserLoader::getNotOrganizationArray();
        }
        $login     = $this->project->getSuperAdminName();
        $twoFactor = $this->project->getSuperAdminTwoFactorKey();

        return array(
            'user_id'                => UserLoader::SYSTEM_USER_ID,
            'user_login'             => $login,
            'user_two_factor_key'    => $twoFactor,
            'user_name'              => $login,
            'user_group'             => -1,
            'user_role'              => 'master',
            'user_style'             => 'gems',
            'user_base_org_id'       => $organizationId,
            'user_allowed_ip_ranges' => $this->project->getSuperAdminIPRanges(),
            'user_blockable'         => false,
            'user_embedded'          => false,
            '__allowedOrgs'          => $orgs,
            );
    }

    /**
     * Should this user be authorized using multi-factor authentication?
     *
     * @param string $ipAddress
     * @param boolean $hasKey
     * @param Group|null $group
     * @return boolean
     */
    public function isTwoFactorRequired(string $ipAddress, bool $hasKey, Group|null $group = null): bool
    {
        if (! $this->project->getSuperAdminTwoFactorKey()) {
            return false;
        }
        $tfExclude = $this->project->getSuperAdminTwoFactorIpExclude();
        if (! $tfExclude) {
            return true;
        }
        return ! $this->util->isAllowedIP($ipAddress, $tfExclude);
    }
}
