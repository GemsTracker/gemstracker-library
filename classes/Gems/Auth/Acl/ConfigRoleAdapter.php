<?php

namespace Gems\Auth\Acl;

class ConfigRoleAdapter implements RoleAdapterInterface
{
    use RoleTrait;

    private const DROP_PRIVILEGES = [
        'pr.setup.access.roles.create',
        'pr.setup.access.roles.edit',
        'pr.setup.access.roles.delete',
    ];

    private readonly array $roles;

    public function __construct(array $config)
    {
        $roles = $config['roles']['roles'] ?? [];
        foreach ($roles as $roleName => $role) {
            $roles[$roleName]['grl_privileges'] = array_diff($role['grl_privileges'], self::DROP_PRIVILEGES);
        }
        $this->roles = $roles;
    }

    public function getRolesConfig(): array
    {
        return $this->roles;
    }

    public function convertKeyToName(mixed $key, bool $loose = false): string
    {
        if (!is_string($key)) {
            throw new \LogicException();
        }

        return $key;
    }

    public function convertNameToKey(string $name): string
    {
        return $name;
    }
}
