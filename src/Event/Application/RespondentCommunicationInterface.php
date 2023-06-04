<?php

namespace Gems\Event\Application;

use Gems\Tracker\Respondent;

interface RespondentCommunicationInterface
{
    public function getCurrentUserId(): int;

    public function getCommunicationJob(): array;

    public function getRespondent(): Respondent;
}