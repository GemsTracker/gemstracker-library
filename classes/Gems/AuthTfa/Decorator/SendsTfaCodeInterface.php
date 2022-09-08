<?php

namespace Gems\AuthTfa\Decorator;

interface SendsTfaCodeInterface
{
    public function sendCode(\Gems\User\User $user): bool;

    public function getSentFeedbackMessage(\Gems\User\User $user): string;
}
