<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;

class TokenEventCommunicationSent extends RespondentCommunicationSent implements TokenEventInterface
{
    const NAME = 'token.communication.sent';

    private Token $token;

    public function __construct(Token $token, int $currentUserId, array $communicationJob = [])
    {
        parent::__construct($token->getRespondent(), $currentUserId, $communicationJob);
        $this->token = $token;
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
}