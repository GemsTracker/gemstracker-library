<?php

namespace Gems\Fake;

use Gems\Db\ResultFetcher;
use Gems\Fake\Organization;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\TrackDataRepository;
use Gems\User\StaffUserDefinition;
use Gems\Util\Translated;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;

class User extends \Gems\User\User
{
    protected int $id = 1;

    protected string $login = 'jdevries';

    protected bool $active = true;

    protected int $baseOrgId = 0;
    protected string|null $email = 'j.de.vries@example.test';
    protected string|null $firstName = 'Jip';
    protected string|null $surnamePrefix = 'de';
    protected string|null $lastName = 'Vries';

    protected string|null $gender = 'M';

    public function __construct(
        StaffUserDefinition $userDefinition,
        OrganizationRepository $organizationRepository,
        AccessRepository $accessRepository,
        TrackDataRepository $trackDataRepository,
        Acl $acl,
        Translated $translatedUtil,
        ResultFetcher $resultFetcher,
        protected readonly UrlHelper $urlHelper,
    ) {
        parent::__construct(
            $userDefinition,
            $organizationRepository,
            $accessRepository,
            $trackDataRepository,
            $acl,
            $translatedUtil,
            $resultFetcher,
        );
    }

    public function getBaseOrganization(): Organization
    {
        $organization = new Organization();
        return $organization;
    }

    public function getPasswordResetKey(): string
    {
        return hash('sha256', random_bytes(64));
    }
}