<?php

namespace Gems\Event\Application;

use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;

class RespondentMailSent extends Event implements RespondentCommunicationInterface
{
    const NAME = 'respondent.mail.sent';

    protected Email $email;

    protected \Gems_Tracker_Respondent $respondent;

    protected \Gems_User_User $currentUser;

    private array $communicationJob;

    public function __construct(Email $email, \Gems_Tracker_Respondent $respondent, \Gems_User_User $currentUser, array $communicationJob = [], )
    {
        $this->email = $email;
        $this->respondent = $respondent;
        $this->currentUser = $currentUser;
        $this->communicationJob = $communicationJob;
    }

    /**
     * @return \Gems_User_User
     */
    public function getCurrentUser(): \Gems_User_User
    {
        return $this->currentUser;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getCommunicationJob(): array
    {
        return $this->communicationJob;
    }

    /**
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent(): \Gems_Tracker_Respondent
    {
        return $this->respondent;
    }
}