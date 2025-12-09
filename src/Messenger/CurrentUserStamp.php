<?php

namespace Gems\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

class CurrentUserStamp implements StampInterface
{
    public function __construct(
        public readonly int|null $userId = null,
        public readonly string|null $username = null,
        public readonly int|null $organizationId = null,
    )
    {
    }
}