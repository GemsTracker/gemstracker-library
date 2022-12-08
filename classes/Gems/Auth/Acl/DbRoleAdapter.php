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
                    RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES => array_values(array_filter(
                        explode(',', $dbRole['grl_privileges']),
                        fn (string $privilege) => !empty(trim($privilege)),
                    )),
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

    public function convertKeyToName(mixed $key, bool $loose = false): string
    {
        $this->fetchRoles();

        if (!isset($this->idMapping[$key])) {
            if ($loose && in_array($key, $this->idMapping, true)) {
                return $key;
            }

            throw new \LogicException();
        }

        return $this->idMapping[$key];
    }

    public function convertNameToKey(string $name): int
    {
        $this->fetchRoles();

        $key = array_search($name, $this->idMapping);

        if ($key === false) {
            throw new \LogicException();
        }

        return $key;
    }
}
