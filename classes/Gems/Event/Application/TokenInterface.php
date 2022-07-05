<?php

namespace Gems\Event\Application;

interface TokenInterface
{
    public function getToken(): \Gems_Tracker_Token;

    public function setToken(\Gems_Tracker_Token $token): void;
}