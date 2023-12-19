<?php

namespace Gems\Messenger\Message;

class SendCommJobMessage
{
    public function __construct(
        private readonly int $jobId,
        private readonly string $tokenId,
    )
    {}

    /**
     * @return int
     */
    public function getJobId(): int
    {
        return $this->jobId;
    }

    /**
     * @return string
     */
    public function getTokenId(): string
    {
        return $this->tokenId;
    }
}