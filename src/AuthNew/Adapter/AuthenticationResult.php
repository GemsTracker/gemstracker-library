<?php

namespace Gems\AuthNew\Adapter;

abstract class AuthenticationResult
{
    public const SUCCESS = 1;
    public const FAILURE = 0;
    public const DISALLOWED_IP = -1;
    public const FAILURE_DEFERRED = -2;

    public function __construct(
        private readonly int $code,
        private readonly ?AuthenticationIdentityInterface $identity,
        private readonly array $messages = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->code === self::SUCCESS;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getIdentity(): ?AuthenticationIdentityInterface
    {
        return $this->identity;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
