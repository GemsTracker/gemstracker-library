<?php

namespace Gems\AuthTfa\Adapter;

interface OtpInterface
{
    public function generateCode(): string;

    public function verify(string $code): bool;
}
