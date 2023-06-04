<?php

namespace Gems\AuthTfa\SendDecorator;

interface SendsOtpCodeInterface
{
    public function sendCode(): bool;

    public function getSentFeedbackMessage(): string;
}
