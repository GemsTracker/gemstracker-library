<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Messenger\Message
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Messenger\Message;

/**
 * @package    Gems
 * @subpackage Messenger\Message
 * @since      Class available since version 1.0
 */
class SendTokenMessage
{
    public function __construct(
        private readonly int $jobId,
        private readonly string $tokenId,
        private readonly array $markedTokens,
        private readonly bool $preview,
        private readonly bool $force,
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

    /**
     * @return array
     */
    public function getMarkedTokens(): array
    {
        return $this->markedTokens;
    }

    /**
     * @return bool
     */
    public function isPreview(): bool
    {
        return $this->preview;
    }

    /**
     * @return bool
     */
    public function isForced(): bool
    {
        return $this->force;
    }
}