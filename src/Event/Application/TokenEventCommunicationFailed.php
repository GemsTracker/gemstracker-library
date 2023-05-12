<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;
use Exception;

class TokenEventCommunicationFailed extends RespondentCommunicationFailed implements TokenEventInterface
{
    const NAME = 'token.communication.sent';

    public function __construct(Exception $exception, protected Token $token, int $currentUserId, array $communicationJob = [])
    {
        parent::__construct($exception, $token->getRespondent(), $currentUserId, $communicationJob);
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
}