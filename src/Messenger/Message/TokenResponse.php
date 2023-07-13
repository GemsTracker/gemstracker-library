<?php

namespace Gems\Messenger\Message;

class TokenResponse
{
    public function __construct(
        private readonly string $tokenId,
        private readonly array $responses,
        private readonly int $userId,
    )
    {
    }

    /**
     * @return string
     */
    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    /**
     * @return array
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

}