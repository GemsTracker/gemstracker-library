<?php

namespace Gems\Repository;

use Gems\Util\UtilDbHelper;

class SourceRepository
{
    public function __construct(protected UtilDbHelper $utilDbHelper)
    {}

    /**
     * Returns the roles in the acl
     *
     * @return array roleId => ucfirst(roleId)
     */
    public function getSources(): array
    {
        $sql = "SELECT gso_id_source, gso_source_name
                    FROM gems__sources
                    ORDER BY gso_source_name";

        return $this->utilDbHelper->getSelectPairsCached(__FUNCTION__, $sql, null, ['sources']);
    }
}