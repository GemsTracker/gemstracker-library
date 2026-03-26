<?php

namespace Gems\Communication\Unsubscribe\Messenger\Message;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class SubscriptionInfo
{
    public function __construct(
        #[Email]
        public readonly string $email,
        public readonly int $organizationId,
        public readonly int $subscriptionValue = 0,
        public readonly string|null $comment = null,
    )
    {
    }
}