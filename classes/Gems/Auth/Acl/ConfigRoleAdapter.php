<?php

namespace Gems\Auth\Acl;

class ConfigRoleAdapter implements RoleAdapterInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function getRoles(): array
    {
        return $this->config['roles'] ?? [];
    }
}
