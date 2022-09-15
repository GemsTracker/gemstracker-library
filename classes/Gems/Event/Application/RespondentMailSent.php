<?php

namespace Gems\Event\Application;

use Gems\Tracker\Respondent;
use Gems\User\User;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;

class RespondentMailSent extends Event implements RespondentCommunicationInterface
{
    const NAME = 'respondent.mail.sent';

    protected Email $email;

    protected Respondent $respondent;

    protected User $currentUser;

    private array $communicationJob;

    public function __construct(Email $email, Respondent $respondent, User $currentUser, array $communicationJob = [], )
    {
        $this->email = $email;
        $this->respondent = $respondent;
        $this->currentUser = $currentUser;
        $this->communicationJob = $communicationJob;
    }

    /**
     * @return User
     */
    public function getCurrentUser(): User
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
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        return $this->respondent;
    }
}