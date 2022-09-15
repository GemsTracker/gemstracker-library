<?php

namespace Gems\Event\Application;

use Gems\Tracker\Respondent;
use Gems\User\User;
use Symfony\Contracts\EventDispatcher\Event;

class RespondentCommunicationSent extends Event implements RespondentCommunicationInterface
{
    const NAME = 'respondent.communication.sent';

    protected Respondent $respondent;

    protected User $currentUser;

    protected array $from = [];

    protected ?string $subject = null;

    protected array $to = [];

    private array $communicationJob;

    public function __construct(Respondent $respondent, User $currentUser, array $communicationJob = [], )
    {
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
     * @return array
     */
    public function getCommunicationJob(): array
    {
        return $this->communicationJob;
    }

    /**
     * @return array
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        return $this->respondent;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return array
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @param array $from
     */
    public function setFrom(array $from): void
    {
        $this->from = $from;
    }

    /**
     * @param string|null $subject
     */
    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @param array $to
     */
    public function setTo(array $to): void
    {
        $this->to = $to;
    }


}