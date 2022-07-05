<?php

namespace Gems\Event\Application;

class TokenCommunicationSent extends RespondentCommunicationSent implements TokenInterface
{
    const NAME = 'token.communication.sent';

    private \Gems_Tracker_Token $token;

    public function __construct(\Gems_Tracker_Token $token, \Gems_User_User $currentUser, array $communicationJob = [])
    {
        parent::__construct($token->getRespondent(), $currentUser, $communicationJob);
        $this->token = $token;
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