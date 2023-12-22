<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;
use Symfony\Contracts\EventDispatcher\Event;

class TokenMarkedAsSent extends Event implements TokenEventInterface
{
    public const NAME = 'token.marked.sent';

    public function __construct(protected Token $token, protected array $jobData)
    {}

    public function getCurrentUserId(): int
    {
        return $this->jobData['gcj_id_user_as'];
    }

    /**
     * @return array
     */
    public function getJobData(): array
    {
        return $this->jobData;
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
}