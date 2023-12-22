<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;

interface TokenEventInterface
{
    public function getCurrentUserId(): int;

    public function getToken(): Token;
}