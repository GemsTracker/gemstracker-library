<?php

namespace Gems\Auth\Acl;

trait RoleTrait
{
    abstract protected function getRolesConfig(): array;

    public function getResolvedRoles(): array
    {
        $resolvedRoles = [];

        foreach (AclRepository::inheritanceSortedRoles($this->getRolesConfig()) as $roleName => $roleConfig) {
            $inheritedPrivileges = [];
            foreach ($roleConfig[RoleAdapterInterface::ROLE_PARENTS] as $parentName) {
                $inheritedPrivileges = array_merge(
                    $inheritedPrivileges,
                    $resolvedRoles[$parentName][RoleAdapterInterface::ROLE_RESOLVED_PRIVILEGES]
                );
            }

            $roleConfig[RoleAdapterInterface::ROLE_INHERITED_PRIVILEGES] = array_values(array_unique($inheritedPrivileges));
            $roleConfig[RoleAdapterInterface::ROLE_RESOLVED_PRIVILEGES] = array_values(array_unique(array_merge(
                $roleConfig[RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES],
                $roleConfig[RoleAdapterInterface::ROLE_INHERITED_PRIVILEGES],
            )));

            $resolvedRoles[$roleName] = $roleConfig;
        }

        return $resolvedRoles;
    }
}
