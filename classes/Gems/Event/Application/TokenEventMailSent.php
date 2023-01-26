<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;
use Gems\User\User;
use Symfony\Component\Mime\Email;

class TokenEventMailSent extends RespondentMailSent implements TokenEventInterface
{
    public const NAME = 'token.mail.sent';

    private Token $token;

    public function __construct(Email $email, Token $token, int $currentUserId, array $communicationJob = [], )
    {
        $this->token = $token;
        parent::__construct($email, $token->getRespondent(), $currentUserId, $communicationJob);
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
}