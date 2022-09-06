<?php

namespace Gems\AuthNew\Adapter;

interface AuthenticationIdentityInterface
{
    public function toArray(): array;

    public static function fromArray(array $array): static;

    public function getLoginName(): string;

    public function getOrganizationId(): int;
}
