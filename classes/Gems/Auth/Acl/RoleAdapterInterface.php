<?php

namespace Gems\Auth\Acl;

interface RoleAdapterInterface
{
    const ROLE_NAME = 'name';
    const ROLE_DESCRIPTION = 'description';
    const ROLE_PARENTS = 'parents';
    const ROLE_ASSIGNED_PRIVILEGES = 'privileges'; // Privileges assigned to a role

    const ROLE_INHERITED_PRIVILEGES = 'inherited_privileges'; // Privileges assigned to ancestors of a role
    const ROLE_RESOLVED_PRIVILEGES = 'resolved_privileges'; // Union of assigned + inherited

    public function getRolesConfig(): array;

    public function getResolvedRoles(): array;
}
