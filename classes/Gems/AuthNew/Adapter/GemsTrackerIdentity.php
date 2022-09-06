<?php

namespace Gems\AuthNew\Adapter;

class GemsTrackerIdentity implements AuthenticationIdentityInterface
{
    public function __construct(
        private readonly string $loginName,
        private readonly int $organizationId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'login_name' => $this->loginName,
            'organization_id' => $this->organizationId,
        ];
    }

    public static function fromArray(array $array): static
    {
        return new static(
            $array['login_name'],
            $array['organization_id'],
        );
    }

    public function getLoginName(): string
    {
        return $this->loginName;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }
}
