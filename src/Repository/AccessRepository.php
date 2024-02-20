<?php

namespace Gems\Repository;

use Gems\User\Group;
use Gems\Util\Translated;
use Gems\Util\UtilDbHelper;
use Zalt\Loader\ProjectOverloader;

class AccessRepository
{
    protected array $cacheTags = [
        'group',
        'groups',
    ];

    protected array $groups = [];

    public function __construct(
        protected Translated $translatedUtil,
        protected UtilDbHelper $utilDbHelper,
        protected ProjectOverloader $projectOverloader,
    )
    {}

    /**
     * Return key/value pairs of all active staff groups
     *
     * @return array
     */
    public function getActiveStaffGroups()
    {
        $staffGroups = $this->utilDbHelper->getTranslatedPairsCached(
                'gems__groups',
                'ggp_id_group',
                'ggp_name',
                ['groups'],
                [
                    'ggp_group_active' =>  1,
                    'ggp_member_type' => 'staff',
                ],
                'natsort');

        return $staffGroups;
    }

    public function getGroup(int $groupId): Group
    {
        if (!isset($this->groups[$groupId])) {
            $this->groups[$groupId] = $this->projectOverloader->create('User\\Group', $groupId);
        }
        return $this->groups[$groupId];
    }

    /**
     * The active groups
     *
     * @return array
     */
    public function getGroups($addEmpty = true)
    {
        return ($addEmpty ? $this->translatedUtil->getEmptyDropdownArray() : []) +
            $this->utilDbHelper->getTranslatedPairsCached(
                'gems__groups',
                'ggp_id_group',
                'ggp_name',
                $this->cacheTags,
                ['ggp_group_active' => 1],
                'natsort'
            );
    }
}