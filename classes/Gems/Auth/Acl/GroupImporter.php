<?php

namespace Gems\Auth\Acl;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class GroupImporter
{
    public function __construct(
        private readonly Adapter $db,
        private readonly DbGroupAdapter $dbAdapter,
        private readonly ConfigGroupAdapter $configAdapter,
    ) {
    }

    public function import(): array
    {
        [
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
        ] = $diff = $this->diff();

        $dbGroups = $this->dbAdapter->getGroupsById();
        $configGroups = $this->configAdapter->getGroupsConfig();

        $codeToId = array_flip(array_map(fn($group) => $group['ggp_code'], $dbGroups));

        $sql = new Sql($this->db);

        if (count($added) > 0) {
            // Keep a register of roles to be added, tracking the dependencies on other roles.
            // Once a role to be added has no dependencies, it can be added
            $dependencyRegister = [];
            foreach ($added as $add) {
                $group = $configGroups[$add];
                $dependencyRegister[$add] = [];
                $dependencyGroups = $group['ggp_may_set_groups'];
                if ($group['ggp_default_group'] !== null) {
                    $dependencyGroups[] = $group['ggp_default_group'];
                }
                $dependencyGroups = array_unique($dependencyGroups);

                foreach ($dependencyGroups as $dependency) {
                    if (!isset($codeToId[$dependency])) {
                        $dependencyRegister[$add][$dependency] = true;
                    }
                }
            }

            while (count($dependencyRegister) > 0) {
                foreach ($dependencyRegister as $addCode => $dependencies) {
                    if (count($dependencies) === 0) {
                        $group = $this->transformGroupToDbFormat($configGroups[$addCode], $codeToId);
                        $group['ggp_changed_by'] = $group['ggp_created_by'] = 0;
                        $group['ggp_changed'] = $group['ggp_created'] = date('Y-m-d H:i:s');

                        // Insert
                        $insert = $sql->insert('gems__groups');
                        $insert->values($group);

                        $statement = $sql->prepareStatementForSqlObject($insert);
                        $result = $statement->execute();
                        $codeToId[$addCode] = $result->getGeneratedValue();

                        // Update dependency array
                        unset($dependencyRegister[$addCode]);
                        foreach($dependencyRegister as $addCode2 => $dependencies2) {
                            unset($dependencyRegister[$addCode2][$addCode]);
                        }
                        continue 2;
                    }
                }
                throw new \Exception('Detected infinite loop');
            }
        }

        if (count($updated) > 0) {
            foreach ($updated as $updateCode) {
                $group = $this->transformGroupToDbFormat($configGroups[$updateCode], $codeToId);
                $group['ggp_changed_by'] = 0;
                $group['ggp_changed'] = date('Y-m-d H:i:s');

                $update = $sql->update('gems__groups');
                $update->set($group);
                $update->where->equalTo('ggp_code', $updateCode);

                $statement = $sql->prepareStatementForSqlObject($update);
                $result = $statement->execute();
                if ($result->getAffectedRows() !== 1) {
                    throw new \Exception();
                }
            }
        }

        if (count($deleted) > 0) {
            $delete = $sql->delete('gems__groups');
            $delete->where->in('ggp_code', $deleted);

            $statement = $sql->prepareStatementForSqlObject($delete);
            $result = $statement->execute();
            if ($result->getAffectedRows() !== count($deleted)) {
                throw new \Exception();
            }
        }

        return $diff;
    }

    private function transformGroupToDbFormat(array $group, array $codeToId): array
    {
        $group['ggp_may_set_groups'] = implode(',', array_map(
            fn (string $groupCode) => $codeToId[$groupCode] ?? throw new \Exception('Invalid group code'),
            $group['ggp_may_set_groups']
        )) ?: null;
        $group['ggp_default_group'] = $group['ggp_default_group'] !== null
            ? $codeToId[$group['ggp_default_group']] ?? throw new \Exception('Invalid group code')
            : null;
        $group['ggp_allowed_ip_ranges'] = implode('|', $group['ggp_allowed_ip_ranges']) ?: null;
        $group['ggp_no_2factor_ip_ranges'] = implode('|', $group['ggp_no_2factor_ip_ranges']) ?: null;

        return $group;
    }

    public function diff(): array
    {
        $dbGroups = $this->dbAdapter->getGroupsConfig();
        $configGroups = $this->configAdapter->getGroupsConfig();

        $dbGroupCodes = array_keys($dbGroups);
        $configGroupCodes = array_keys($configGroups);

        $added = array_diff($configGroupCodes, $dbGroupCodes);
        $deleted = array_diff($dbGroupCodes, $configGroupCodes);

        $groupsInBoth = array_intersect($dbGroupCodes, $configGroupCodes);

        $updated = [];
        foreach ($groupsInBoth as $groupCode) {
            if ($dbGroups[$groupCode] !== $configGroups[$groupCode]) {
                $updated[] = $groupCode;
            }
        }

        return [
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }
}
