<?php

namespace Gems\AuthNew\Adapter;

class EmbedIdentity implements AuthenticationIdentityInterface
{
    public function __construct(
        private readonly string $systemUserLoginName,
        private readonly string $deferredLoginName,
        private readonly string $patientId,
        private readonly int $organizationId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'system_user_login_name' => $this->systemUserLoginName,
            'deferred_login_name' => $this->deferredLoginName,
            'patient_id' => $this->patientId,
            'organization_id' => $this->organizationId,
        ];
    }

    public static function fromArray(array $array): static
    {
        return new static(
            $array['system_user_login_name'],
            $array['deferred_login_name'],
            $array['patient_id'],
            $array['organization_id'],
        );
    }

    public function getLoginName(): string
    {
        return $this->deferredLoginName;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    public function getSystemUserLoginName(): string
    {
        return $this->systemUserLoginName;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }
}
