<?php

namespace Gems\Event\Application;

use Gems\Tracker\Respondent;
use Gems\User\User;

interface RespondentCommunicationInterface
{
    public function getCurrentUser(): User;

    public function getCommunicationJob(): array;

    public function getRespondent(): Respondent;
}