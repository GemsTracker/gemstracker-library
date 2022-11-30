<?php

namespace Gems\Auth\Acl;

class ConfigRoleAdapter implements RoleAdapterInterface
{
    use RoleTrait;

    public function __construct(private readonly array $config)
    {
    }

    public function getRolesConfig(): array
    {
        return $this->config['roles']['roles'] ?? [];
    }

    public function convertKeyToName(mixed $key): string
    {
        if (!is_string($key)) {
            throw new \LogicException();
        }

        return $key;
    }
}
