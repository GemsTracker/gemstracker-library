<?php

namespace Gems\Screens;

use Gems\Repository\OrganizationRepository;
use Gems\Screens\Respondent\Subscribe\EmailOnlySubscribe;
use Gems\Screens\Respondent\Unsubscribe\EmailOnlyUnsubscribe;
use Zalt\Loader\ProjectOverloader;

class ScreenRepository
{
    public function __construct(
        protected readonly ProjectOverloader $overloader,
        protected readonly OrganizationRepository $organizationRepository,
    )
    {
    }

    public function getSubscribeScreenForOrganizationId(?int $organizationId = null): SubscribeScreenInterface
    {
        $organization = $this->organizationRepository->getOrganization($organizationId);
        return $organization->getSubscribeScreen() ?? $this->overloader->create(EmailOnlySubscribe::class);
    }

    public function getUnSubscribeScreenForOrganizationId(?int $organizationId = null, ?int $groupId = null): UnsubscribeScreenInterface
    {
        $organization = $this->organizationRepository->getOrganization($organizationId);
        return $organization->getUnsubscribeScreen() ?? $this->overloader->create(EmailOnlyUnsubscribe::class);
    }
}
