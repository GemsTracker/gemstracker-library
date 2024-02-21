<?php

namespace Gems\AuthTfa\Adapter;

use Gems\User\User;

interface OtpAdapterInterface
{
    public function generateSecret(): string;

    public function generateCode(User $user): string;

    public function verify(User $user, string $code): bool;

    public function getCodeValidSeconds(): int;

    public function getMinLength(): int;

    public function getMaxLength(): int;

    public function canVerifyOtp(User $user): bool;

    public function hitVerifyOtp(User $user): void;
}