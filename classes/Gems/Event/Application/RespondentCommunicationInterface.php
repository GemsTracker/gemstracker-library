<?php

namespace Gems\Event\Application;

interface RespondentCommunicationInterface
{
    public function getCurrentUser(): \Gems_User_User;

    public function getCommunicationJob(): array;

    public function getRespondent(): \Gems_Tracker_Respondent;
}