<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsOrganizationIdBigintPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Ensure columns having an organization id are always of type bigint';
    }

    public function getOrder(): int
    {
        return 20231203000001;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__radius_config MODIFY COLUMN grcfg_id_organization bigint unsigned not null',
        ];

        return $statements;
    }
}
