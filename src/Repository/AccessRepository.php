<?php

namespace Gems\Repository;

use Gems\Util\Translated;
use Gems\Util\UtilDbHelper;

class AccessRepository
{
    protected array $cacheTags = [
        'group',
        'groups',
    ];

    public function __construct(protected Translated $translatedUtil, protected UtilDbHelper $utilDbHelper)
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