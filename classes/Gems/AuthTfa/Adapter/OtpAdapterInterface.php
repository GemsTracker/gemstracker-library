<?php

namespace Gems\AuthTfa\Adapter;

interface OtpAdapterInterface
{
    public function generateCode(): string;

    public function verify(string $code): bool;
}
