<?php

namespace Gems\Auth\Acl;

use Brick\VarExporter\VarExporter;
use Gems\Menu\RouteHelper;
use Gems\UntranslatedString;
use Gems\User\User;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource;
use Laminas\Permissions\Acl\Role\GenericRole;

class AclRepository
{
    private ?Acl $acl = null;

    public function __construct(
        private readonly array $config,
        public readonly RoleAdapterInterface $roleAdapter,
    ) {
    }

    public function getAcl(): Acl
    {
        if (!$this->acl) {
            $this->loadAcl();
        }

        return $this->acl;
    }

    public function hasRolesFromConfig(): bool
    {
        return $this->roleAdapter instanceof ConfigRoleAdapter;
    }

    public static function inheritanceSortedRoles(array $rolesConfig): \Generator
    {
        $visitedRoleNames = [];
        $resolve = function (string $roleName) use ($rolesConfig, &$visitedRoleNames, &$resolve) {
            $roleConfig = $rolesConfig[$roleName];

            $unvisitedRoleNames = array_diff($roleConfig[RoleAdapterInterface::ROLE_PARENTS], $visitedRoleNames);

            foreach ($unvisitedRoleNames as $unvisitedRoleName) {
                yield from $resolve($unvisitedRoleName);
            }

            $visitedRoleNames[] = $roleName;
            yield $roleName => $roleConfig;
        };

        foreach ($rolesConfig as $roleName => $roleConfig) {
            if (!in_array($roleName, $visitedRoleNames)) {
                yield from $resolve($roleName);
            }
        }
    }

    private function loadAcl(): void
    {
        $this->acl = new Acl();

        foreach ($this->getDefinedPrivileges() as $privilege) {
            $this->acl->addResource(new GenericResource($privilege));
        }

        foreach (self::inheritanceSortedRoles($this->roleAdapter->getRolesConfig()) as $roleName => $roleConfig) {
            if ($roleName !== $roleConfig[RoleAdapterInterface::ROLE_NAME]) {
                throw new \Exception('Role name mismatch');
            }

            $this->registerRole($roleConfig);
        }
    }

    private function registerRole(array $roleConfig): void
    {
        $role = new GenericRole($roleConfig[RoleAdapterInterface::ROLE_NAME]);
        if ($this->acl->hasRole($role)) {
            return;
        }

        $this->acl->addRole($role, $roleConfig[RoleAdapterInterface::ROLE_PARENTS]);

        foreach ($roleConfig[RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES] as $privilege) {
            if ($this->acl->hasResource($privilege)) {
                $this->acl->allow($role, $privilege);
            }
        }
    }

    public function getChildren(string $roleName): array
    {
        $children = [];
        foreach ($this->roleAdapter->getRolesConfig() as $role) {
            if (in_array($roleName, $role[RoleAdapterInterface::ROLE_PARENTS])) {
                $children[] = $role[RoleAdapterInterface::ROLE_NAME];
            }
        }

        return $children;
    }

    public function getResolvedRoles(): array
    {
        return $this->roleAdapter->getResolvedRoles();
    }

    public function convertKeyToName(mixed $key, bool $loose = false): string
    {
        return $this->roleAdapter->convertKeyToName($key, $loose);
    }

    public function convertKeysToNames(array|string|null $keys, bool $loose = false): array
    {
        return array_map(fn($key) => $this->convertKeyToName($key, $loose), is_string($keys) ? explode(',', $keys) : ($keys ?? []));
    }

    public function convertNameToKey(string $name): mixed
    {
        return $this->roleAdapter->convertNameToKey($name);
    }

    public function convertNamesToKeys(array|string|null $names): array
    {
        return array_map($this->convertNameToKey(...), is_string($names) ? explode(',', $names) : ($names ?? []));
    }

    public function getDefinedPrivileges(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->getSupplementaryPrivileges()),
            array_keys(RouteHelper::getAllRoutePrivilegesFromConfig($this->config['routes'])),
        )));
    }

    /**
     * @return UntranslatedString[]
     */
    public function getSupplementaryPrivileges(): array
    {
        return $this->config['supplementary_privileges'];
    }

    /**
     * Returns the roles in the acl
     *
     * @return array roleId => ucfirst(roleId)
     */
    public function getRoleValues(): array
    {
        $roles = [];

        foreach ($this->getAcl()->getRoles() as $role) {
            //Do not translate, only make first one uppercase
            $roles[$role] = ucfirst($role);
        }
        asort($roles);

        return $roles;
    }

    /**
     * Returns the current roles a user may set.
     *
     * NOTE! A user can set a role, unless it <em>requires a higher role level</em>.
     *
     * I.e. an admin is not allowed to set a super role as super inherits and expands admin. But it is
     * allowed to set the nologin and respondent roles that are not inherited by the admin as they are
     * in a different hierarchy.
     *
     * An exception is the role master as it is set by the system. You gotta be a master to set the master
     * role.
     *
     * @return string[] With identical keys and values roleId => roleId
     */
    public function getAllowedRoles(User $user): array
    {
        $userRole = $user->getRole();
        if ($userRole === 'master') {
            $output = $this->acl->getRoles();
            return array_combine($output, $output);
        }

        $output = [$userRole => $userRole];
        foreach ($this->acl->getRoles() as $role) {
            if (! $this->acl->inheritsRole($role, $userRole, true)) {
                $output[$role] = $role;
            }
        }
        unset($output['master']);
        return $output;
    }

    public function buildRoleConfigFile(): string
    {
        $template = '<?php

return [
    \'roles\' => [
        \'definition_date\' => \'' . date('Y-m-d H:i:s') . '\',
        \'roles\' => %s,
    ],
];' . PHP_EOL;

        $roles = VarExporter::export($this->roleAdapter->getRolesConfig(), VarExporter::TRAILING_COMMA_IN_ARRAY, 2);

        return sprintf($template, $roles);
    }
}
