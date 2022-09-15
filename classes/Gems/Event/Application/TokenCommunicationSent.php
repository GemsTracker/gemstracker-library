<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;
use Gems\User\User;

class TokenCommunicationSent extends RespondentCommunicationSent implements TokenInterface
{
    const NAME = 'token.communication.sent';

    private Token $token;

    public function __construct(Token $token, User $currentUser, array $communicationJob = [])
    {
        parent::__construct($token->getRespondent(), $currentUser, $communicationJob);
        $this->token = $token;
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