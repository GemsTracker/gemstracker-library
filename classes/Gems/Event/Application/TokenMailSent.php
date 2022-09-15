<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;
use Gems\User\User;
use Symfony\Component\Mime\Email;

class TokenMailSent extends RespondentMailSent implements TokenInterface
{
    public const NAME = 'token.mail.sent';

    private Token $token;

    public function __construct(Email $email, Token $token, User $currentUser, array $communicationJob = [], )
    {
        $this->token = $token;
        parent::__construct($email, $token->getRespondent(), $currentUser, $communicationJob);
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