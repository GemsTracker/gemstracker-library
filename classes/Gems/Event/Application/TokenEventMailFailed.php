<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;
use Symfony\Component\Mime\Email;
use Exception;

class TokenEventMailFailed extends RespondentMailFailed implements TokenEventInterface
{
    public const NAME = 'token.mail.sent';

    private Token $token;

    public function __construct(Exception $exception, Email $email, Token $token, int $currentUserId, array $communicationJob = [], )
    {
        $this->token = $token;
        parent::__construct($exception, $email, $token->getRespondent(), $currentUserId, $communicationJob);
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
}