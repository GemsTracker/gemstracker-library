<?php

namespace Gems\Auth\Acl;

use Gems\Db\ResultFetcher;

class DbGroupAdapter implements GroupAdapterInterface
{
    private ?array $groupsById = null;

    private ?array $groupsByCode = null;

    public function __construct(private readonly ResultFetcher $resultFetcher)
    {
    }

    private function fetchGroups(): void
    {
        if ($this->groupsById === null) {
            $select = $this->resultFetcher->getSelect();
            $select->from('gems__groups');

            $dbGroups = $this->resultFetcher->fetchAll($select);

            $idToCode = [];
            foreach ($dbGroups as $dbGroup) {
                $idToCode[$dbGroup['ggp_id_group']] = $dbGroup['ggp_code'];
            }

            $groupsById = [];
            $groupsByCode = [];
            foreach ($dbGroups as $dbGroup) {
                $group = [
                    'ggp_code' => $dbGroup['ggp_code'],
                    'ggp_name' => $dbGroup['ggp_name'],
                    'ggp_description' => $dbGroup['ggp_description'],
                    'ggp_role' => $dbGroup['ggp_role'],
                    'ggp_may_set_groups' => array_map(function ($group) use ($idToCode) {
                        return $idToCode[$group] ?? throw new \Exception('Invalid group id');
                    }, array_values(array_filter(explode(',', $dbGroup['ggp_may_set_groups'] ?? '')))),
                    'ggp_default_group' => $dbGroup['ggp_default_group'] !== null
                        ? $idToCode[$dbGroup['ggp_default_group']]  ?? throw new \Exception('Invalid group id')
                        : null,
                    'ggp_group_active' => $dbGroup['ggp_group_active'],
                    'ggp_member_type' => $dbGroup['ggp_member_type'],
                    'ggp_allowed_ip_ranges' => array_values(array_filter(explode('|', $dbGroup['ggp_allowed_ip_ranges'] ?? ''))),
                    'ggp_no_2factor_ip_ranges' => array_values(array_filter(explode('|', $dbGroup['ggp_no_2factor_ip_ranges'] ?? ''))),
                    'ggp_2factor_set' => $dbGroup['ggp_2factor_set'],
                    'ggp_2factor_not_set' => $dbGroup['ggp_2factor_not_set'],
                ];

                $groupsById[$dbGroup['ggp_id_group']] = $group;
                $groupsByCode[$dbGroup['ggp_code']] = $group;
            }

            if (
                count($idToCode) !== count(array_unique($idToCode))
                || count($idToCode) !== count($dbGroups)
                || count($idToCode) !== count($groupsById)
                || count($idToCode) !== count($groupsByCode)
            ) {
                throw new \Exception('Found duplicate group names');
            }

            $this->groupsById = $groupsById;
            $this->groupsByCode = $groupsByCode;
        }
    }

    public function getGroupsConfig(): array
    {
        $this->fetchGroups();

        return $this->groupsByCode;
    }

    public function getGroupsById(): array
    {
        $this->fetchGroups();

        return $this->groupsById;
    }
}
