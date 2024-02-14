<?php

namespace Gems\Fake;

use Gems\Db\ResultFetcher;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\User\StaffUserDefinition;
use Gems\Fake\Organization;
use Gems\Util\Translated;
use Laminas\Permissions\Acl\Acl;

class User extends \Gems\User\User
{
    public function __construct(
        StaffUserDefinition $userDefinition,
        OrganizationRepository $organizationRepository,
        AccessRepository $accessRepository,
        Acl $acl,
        Translated $translatedUtil,
        ResultFetcher $resultFetcher,
    ) {
        parent::__construct(
            $userDefinition,
            $organizationRepository,
            $accessRepository,
            $acl,
            $translatedUtil,
            $resultFetcher,
        );
    }

    public function getBaseOrganization(): Organization
    {
        return new Organization();
    }

    protected function getUserData(): array
    {
        return [
            'user_id' => 1,
            'user_login' => 'jdevries',
            'user_last_name' => 'Vries',
            'user_surname_prefix' => 'de',
            'user_first_name' => 'Jip',
            'user_gender' => 'M',
            'user_email' => 'j.de.vries@example.test',
            'user_role'  => null,
        ];
    }

    public function getPasswordResetKey(): string
    {
        return hash('sha256', random_bytes(64));
    }

    public function setRole(?string $role): void
    {
        $this->_setVar('user_role', $role);
    }
}