<?php

namespace Gems\Auth\Acl;

use Gems\Db\ResultFetcher;

class DbRoleAdapter implements RoleAdapterInterface
{
    use RoleTrait;

    private ?array $roles = null;

    private ?array $idMapping = null;

    public function __construct(private readonly ResultFetcher $resultFetcher)
    {
    }

    private function fetchRoles(): array
    {
        if ($this->roles === null) {
            $select = $this->resultFetcher->getSelect();
            $select->from('gems__roles');

            $dbRoles = $this->resultFetcher->fetchAll($select);

            $this->idMapping = [];
            foreach ($dbRoles as $dbRole) {
                $this->idMapping[$dbRole['grl_id_role']] = $dbRole['grl_name'];
            }
            if (count($this->idMapping) !== count(array_unique($this->idMapping))) {
                throw new \Exception('Found duplicate role names');
            }

            $roles = [];
            foreach ($dbRoles as $dbRole) {
                $roles[$dbRole['grl_name']] = [
                    RoleAdapterInterface::ROLE_NAME => $dbRole['grl_name'],
                    RoleAdapterInterface::ROLE_DESCRIPTION => $dbRole['grl_description'],
                    RoleAdapterInterface::ROLE_PARENTS => array_map(function ($id) {
                        return $this->idMapping[$id] ?? throw new \Exception('Could not find parent ' . $id);
                    }, $dbRole['grl_parents'] === null ? [] : explode(',', $dbRole['grl_parents'])),
                    RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES => explode(',', $dbRole['grl_privileges']),
                ];
            }

            $this->roles = $roles;
        }

        return $this->roles;
    }

    public function getRolesConfig(): array
    {
        return $this->fetchRoles();
    }
}
