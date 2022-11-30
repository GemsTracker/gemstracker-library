<?php

namespace Gems\Auth\Acl;

interface RoleAdapterInterface
{
    const ROLE_NAME = 'grl_name';
    const ROLE_DESCRIPTION = 'grl_description';
    const ROLE_PARENTS = 'grl_parents';
    const ROLE_ASSIGNED_PRIVILEGES = 'grl_privileges'; // Privileges assigned to a role

    const ROLE_INHERITED_PRIVILEGES = 'inherited_privileges'; // Privileges assigned to ancestors of a role
    const ROLE_RESOLVED_PRIVILEGES = 'resolved_privileges'; // Union of assigned + inherited

    public function getRolesConfig(): array;

    public function getResolvedRoles(): array;

    public function convertKeyToName(mixed $key): string;
}
