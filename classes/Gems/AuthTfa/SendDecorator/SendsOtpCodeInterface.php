<?php

namespace Gems\AuthTfa\SendDecorator;

interface SendsOtpCodeInterface
{
    public function sendCode(\Gems\User\User $user): bool;

    public function getSentFeedbackMessage(\Gems\User\User $user): string;
}
