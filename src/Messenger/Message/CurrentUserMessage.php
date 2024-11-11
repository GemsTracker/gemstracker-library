<?php

declare(strict_types=1);

namespace Gems\Messenger\Message;

class CurrentUserMessage
{
    public function __construct(
        protected readonly string $userName,
        protected readonly int $organizationId
    )
    {
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }
}