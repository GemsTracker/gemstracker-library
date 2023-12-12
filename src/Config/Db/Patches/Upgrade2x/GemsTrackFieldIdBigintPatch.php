<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTrackFieldIdBigintPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Ensure columns having a track field id are always of type bigint';
    }

    public function getOrder(): int
    {
        return 20231203000001;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__log_respondent2track2field MODIFY COLUMN glrtf_id_field bigint unsigned not null',
        ];

        return $statements;
    }
}
