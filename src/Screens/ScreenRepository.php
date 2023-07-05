<?php

namespace Gems\Screens;

use Gems\Screens\Respondent\Subscribe\EmailOnlySubscribe;
use Gems\Screens\Respondent\Unsubscribe\EmailOnlyUnsubscribe;
use Zalt\Loader\ProjectOverloader;

class ScreenRepository
{
    public function __construct(private ProjectOverloader $overloader)
    {
    }

    public function getSubscribeScreenForOrganizationId(?int $organizationId = null, ?int $groupId = null): SubscribeScreenInterface
    {
        return $this->overloader->create(EmailOnlySubscribe::class);
    }

    public function getUnSubscribeScreenForOrganizationId(?int $organizationId = null, ?int $groupId = null): UnsubscribeScreenInterface
    {
        return $this->overloader->create(EmailOnlyUnsubscribe::class);
    }
}
