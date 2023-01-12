<?php

namespace Gems\Auth\Acl;

class ConfigRoleAdapter implements RoleAdapterInterface
{
    use RoleTrait;

    private const DROP_PRIVILEGES = [
        'pr.setup.access.roles.create',
        'pr.setup.access.roles.edit',
        'pr.setup.access.roles.delete',
        'pr.setup.access.roles.download',
        'pr.setup.access.roles.diff',
        'pr.setup.access.groups.create',
        'pr.setup.access.groups.edit',
        'pr.setup.access.groups.delete',
        'pr.setup.access.groups.download',
    ];

    private readonly array $roles;

    public function __construct(private readonly array $config)
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

    public function getDefinitionDate(): \DateTime
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $this->config['roles']['definition_date']);
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
