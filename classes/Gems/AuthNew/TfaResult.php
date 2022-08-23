<?php

namespace Gems\AuthNew;

use Gems\User\User;

class TfaResult
{
    public const SUCCESS = 1;
    public const FAILURE = 0;

    public function __construct(
        private readonly TfaAdapterType $type,
        private readonly int $code,
        private readonly User $user,
        private readonly array $messages = []
    ) {
    }

    public function getTfaType(): TfaAdapterType
    {
        return $this->type;
    }

    public function isValid(): bool
    {
        return $this->code === self::SUCCESS;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
