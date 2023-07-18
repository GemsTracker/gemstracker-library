<?php

namespace Gems\Tracker;

use Gems\ReceptionCode\ReceptionCodeType;

class ReceptionCode
{
    public function __construct(
        private readonly string $code,
        private readonly ReceptionCodeType $type,
        private readonly bool $success,
        private readonly ?string $description = null,
        private readonly bool $redoSurvey = false,
        private readonly bool $redoCopy = false,
        private readonly bool $stop = false,
        private readonly bool $overwriteAnswers = false,
    )
    {}

    public function __toString(): string
    {
        return $this->getCode();
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function hasDescription(): bool
    {
        return (bool)$this->description;
    }

    public function hasRedoCode(): bool
    {
        return $this->redoSurvey;
    }

    public function hasRedoCopyCode(): bool
    {
        return $this->redoSurvey && $this->redoCopy;
    }

    public function isForRespondents(): bool
    {
        return $this->type === ReceptionCodeType::RESPONDENT;
    }

    public function isForSurveys(): bool
    {
        return $this->type === ReceptionCodeType::SURVEY;
    }

    public function isForTracks(): bool
    {
        return $this->type === ReceptionCodeType::TRACK;
    }

    public function isOverwriter(): bool
    {
        return $this->overwriteAnswers;
    }

    public function isStopCode(): bool
    {
        return $this->isForSurveys() && $this->stop;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
}