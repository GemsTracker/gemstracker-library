<?php

namespace Gems\Event\Application;

use Gems\Tracker\Respondent;
use Symfony\Contracts\EventDispatcher\Event;
use Exception;

class RespondentCommunicationFailed extends Event implements RespondentCommunicationInterface
{
    const NAME = 'respondent.communication.failed';

    protected Exception $exception;

    protected Respondent $respondent;

    protected int $currentUserId;

    protected array $from = [];

    protected ?string $subject = null;

    protected array $to = [];

    private array $communicationJob;

    public function __construct(Exception $exception, Respondent $respondent, int $currentUserId, array $communicationJob = [], )
    {
        $this->respondent = $respondent;
        $this->currentUserId = $currentUserId;
        $this->communicationJob = $communicationJob;
        $this->exception = $exception;
    }
    public function getCurrentUserId(): int
    {
        return $this->currentUserId;
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