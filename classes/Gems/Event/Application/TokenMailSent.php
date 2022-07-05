<?php

namespace Gems\Event\Application;

use Symfony\Component\Mime\Email;

class TokenMailSent extends RespondentMailSent
{
    public const NAME = 'token.mail.sent';

    private \Gems_Tracker_Token $token;

    public function __construct(Email $email, \Gems_Tracker_Token $token, \Gems_User_User $currentUser, array $mailJob = [], )
    {
        $this->token = $token;
        parent::__construct($email, $token->getRespondent(), $currentUser, $mailJob);
    }

    /**
     * @return \Gems_Tracker_Token
     */
    public function getToken(): \Gems_Tracker_Token
    {
        return $this->token;
    }


}