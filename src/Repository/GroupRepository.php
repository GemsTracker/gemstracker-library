<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;

class GroupRepository
{
    protected array $cacheTags = [
        'group',
        'groups',
    ];

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
    )
    {
    }





    public function getActiveGroupsData(): array
    {
        return array_filter($this->getGroupsData(), function($groupRow) {
           return ((bool)$groupRow['ggp_group_active']);
        });
    }

    public function getGroupMemberType(int $groupId): string|null
    {
        $groupData = $this->getGroupData($groupId);

        if ($groupData && isset($groupData['ggp_member_type'])) {
            return $groupData['ggp_member_type'];
        }
        return null;
    }

    public function getGroupData(int $groupId): array|null
    {
        $groups = $this->getGroupsData();
        foreach($groups as $group) {
            if ($group['ggp_id_group'] === $groupId) {
                return $group;
            }
        }
        return null;
    }

    public function getGroupsData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__groups');

        return $this->cachedResultFetcher->fetchAll(__CLASS__ . __FUNCTION__, $select, null, $this->cacheTags);
    }

    public function getGroupOptions(): array
    {
        $groups = $this->getGroupsData();
        return array_column($groups, 'ggp_name', 'ggp_id_group');
    }

    public function getRespondentGroupOptions(): array
    {
        $staffGroups = array_filter($this->getGroupsData(), function($groupRow) {
            return ($groupRow['ggp_group_active']) && $groupRow['ggp_member_type'] === 'respondent';
        });
        $options = array_column($staffGroups, 'ggp_name', 'ggp_id_group');
        natsort($options);
        return $options;
    }

    public function getStaffGroupOptions(): array
    {
        $staffGroups = array_filter($this->getGroupsData(), function($groupRow) {
            return ($groupRow['ggp_group_active']) && $groupRow['ggp_member_type'] === 'staff';
        });
        $options = array_column($staffGroups, 'ggp_name', 'ggp_id_group');
        natsort($options);
        return $options;
    }
}
