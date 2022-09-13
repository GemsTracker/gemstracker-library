<?php

namespace Gems\AuthTfa\Adapter;

interface OtpAdapterInterface
{
    public function generateCode(): string;

    public function verify(string $code): bool;

    public function getCodeValidSeconds(): int;

    public function getMinLength(): int;

    public function getMaxLength(): int;
}
