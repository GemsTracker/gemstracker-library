<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsCommJobsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__comm_jobs for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__comm_jobs MODIFY COLUMN gcj_id_communication_messenger bigint unsigned NOT NULL',
            'ALTER TABLE gems__comm_jobs MODIFY COLUMN gcj_to_method varchar(1) DEFAULT "A"',
        ];
    }
}
