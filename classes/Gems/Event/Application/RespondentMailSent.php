<?php

namespace Gems\Event\Application;

use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;

class RespondentMailSent extends Event
{
    const NAME = 'respondent.mail.sent';

    protected Email $email;

    protected \Gems_Tracker_Respondent $respondent;

    protected \Gems_User_User $currentUser;

    private array $mailJob;

    public function __construct(Email $email, \Gems_Tracker_Respondent $respondent, \Gems_User_User $currentUser, array $mailJob = [], )
    {
        $this->email = $email;
        $this->respondent = $respondent;
        $this->currentUser = $currentUser;
        $this->mailJob = $mailJob;
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
    public function getMailJob(): array
    {
        return $this->mailJob;
    }

    /**
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent(): \Gems_Tracker_Respondent
    {
        return $this->respondent;
    }
}