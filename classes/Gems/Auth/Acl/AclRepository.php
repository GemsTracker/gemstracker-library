<?php

namespace Gems\Auth\Acl;

use Gems\MenuNew\RouteHelper;
use Gems\UntranslatedString;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource;
use Laminas\Permissions\Acl\Role\GenericRole;

class AclRepository
{
    private ?Acl $acl = null;

    public function __construct(
        private readonly array $config,
        private readonly RoleAdapterInterface $roleAdapter,
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

        $this->acl->addRole($role, $roleConfig[RoleAdapterInterface::ROLE_PARENTS]);

        foreach ($roleConfig[RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES] as $privilege) {
            if ($this->acl->hasResource($privilege)) {
                $this->acl->allow($role, $privilege);
            }
        }
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
            RouteHelper::getAllRoutePrivilegesFromConfig($this->config['routes']),
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
}
