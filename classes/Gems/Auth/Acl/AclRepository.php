<?php

namespace Gems\Auth\Acl;

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

    private function loadAcl(): void
    {
        $this->acl = new Acl();

        foreach ($this->config['permissions'] as $permission) {
            $this->acl->addResource(new GenericResource($permission));
        }

        foreach ($this->roleAdapter->getRoles() as $role => $permissions) {
            $this->acl->addRole(new GenericRole($role));

            foreach ($permissions as $permission) {
                $this->acl->allow($role, $permission);
            }
        }
    }
}
