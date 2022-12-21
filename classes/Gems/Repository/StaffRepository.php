<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;

class StaffRepository
{
    public function __construct(protected CachedResultFetcher $cachedResultFetcher)
    {}

    /**
     * Return key/value pairs of all active staff members
     *
     * @return array
     */
    public function getActiveStaff()
    {
        $sql = "SELECT gsf_id_user,
                    CONCAT(
                        COALESCE(gsf_last_name, '-'),
                        ', ',
                        COALESCE(gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                        ) AS name
                FROM gems__staff
                WHERE gsf_active = 1
                ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix";

        return $this->cachedResultFetcher->fetchPairs(__FUNCTION__, $sql, null, ['staff']);
    }
}