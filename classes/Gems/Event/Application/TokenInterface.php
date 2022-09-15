<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;

interface TokenInterface
{
    public function getToken(): Token;

    public function setToken(Token $token): void;
}