<?php

namespace Gems\Event\Application;

use Gems\Tracker\Respondent;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;
use Exception;

class RespondentMailFailed extends Event implements RespondentCommunicationInterface
{
    const NAME = 'respondent.mail.failed';

    protected Email $email;

    protected Exception $exception;

    protected Respondent $respondent;

    protected int $currentUser;

    private array $communicationJob;

    public function __construct(Exception $exception, Email $email, Respondent $respondent, int $currentUserId, array $communicationJob = [], )
    {
        $this->exception = $exception;
        $this->email = $email;
        $this->respondent = $respondent;
        $this->currentUserId = $currentUserId;
        $this->communicationJob = $communicationJob;
    }

    public function getCurrentUserId(): int
    {
        return $this->currentUserId;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->exception;
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