<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTrackIdBigintPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Ensure columns having a track id are always of type bigint';
    }

    public function getOrder(): int
    {
        return 20231203000001;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__comm_jobs MODIFY COLUMN gcj_id_track bigint unsigned null',
            'ALTER TABLE gems__respondent2track MODIFY COLUMN gr2t_id_track bigint unsigned not null',
            'ALTER TABLE gems__track_appointments MODIFY COLUMN gtap_id_track bigint unsigned not null',
            'ALTER TABLE gems__track_fields MODIFY COLUMN gtf_id_track bigint unsigned not null',
            'ALTER TABLE gems__tracks MODIFY COLUMN gtr_id_track bigint unsigned not null auto_increment',
        ];

        return $statements;
    }
}
