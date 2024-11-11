<?php

namespace Gems\Auth\Acl;

use Gems\Db\ResultFetcher;
use Gems\Event\Application\RoleGatherPrivilegeDropsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DbRoleAdapter implements RoleAdapterInterface
{
    use RoleTrait;

    private ?array $roles = null;

    private ?array $idMapping = null;

    public function __construct(
        private readonly ResultFetcher $resultFetcher,
        private readonly array $config,
        private readonly EventDispatcher $dispatcher,
    ) {
    }

    private function fetchRoles(): array
    {
        if ($this->roles === null) {
            $dropPrivilegesEvent = new RoleGatherPrivilegeDropsEvent();
            $this->dispatcher->dispatch($dropPrivilegesEvent);

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
                $privileges = $dbRole['grl_privileges'] ?? '';
                $privileges = array_filter(
                    explode(',', $privileges),
                    fn(string $privilege) => !empty(trim($privilege)),
                );

                $privileges = array_diff($privileges, $dropPrivilegesEvent->getDroppedPrivileges());

                if (empty($this->config['roles']) && empty($this->config['groups'])) {
                    $privileges = array_diff($privileges, [
                        'pr.setup.access.roles.download',
                        'pr.setup.access.roles.diff',
                        'pr.setup.access.groups.download',
                        'pr.setup.access.groups.diff',
                    ]);
                }

                $roles[$dbRole['grl_name']] = [
                    RoleAdapterInterface::ROLE_NAME => $dbRole['grl_name'],
                    RoleAdapterInterface::ROLE_DESCRIPTION => $dbRole['grl_description'],
                    RoleAdapterInterface::ROLE_PARENTS => array_map(function ($id) {
                        return $this->idMapping[$id] ?? throw new \Exception('Could not find parent ' . $id);
                    }, $dbRole['grl_parents'] === null ? [] : explode(',', $dbRole['grl_parents'])),
                    RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES => array_values($privileges),
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
