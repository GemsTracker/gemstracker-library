<?php

namespace Gems\Event\Application;

use Symfony\Component\Mime\Email;

class TokenMailSent extends RespondentMailSent implements TokenInterface
{
    public const NAME = 'token.mail.sent';

    private \Gems_Tracker_Token $token;

    public function __construct(Email $email, \Gems_Tracker_Token $token, \Gems_User_User $currentUser, array $communicationJob = [], )
    {
        $this->token = $token;
        parent::__construct($email, $token->getRespondent(), $currentUser, $communicationJob);
    }

    /**
     * @return \Gems_Tracker_Token
     */
    public function getToken(): \Gems_Tracker_Token
    {
        return $this->token;
    }

    public function setToken(\Gems_Tracker_Token $token): void
    {
        $this->token = $token;
    }
}