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
        private readonly CommJob $job,
        private readonly string $tokenId,
        private readonly array $markedTokens,
    )
    {}

    /**
     * @return CommJob
     */
    public function getJob(): CommJob
    {
        return $this->job;
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
}