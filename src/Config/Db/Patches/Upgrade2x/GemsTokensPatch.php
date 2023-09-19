<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTokensPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__tokens for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__tokens MODIFY COLUMN gto_id_relation bigint DEFAULT NULL AFTER gto_id_relationfield',
            "ALTER TABLE gems__tokens MODIFY COLUMN gto_in_source tinyint(1) NOT NULL DEFAULT '0' AFTER gto_start_time",
            'ALTER TABLE gems__tokens MODIFY COLUMN gto_by bigint unsigned DEFAULT NULL AFTER gto_in_source',
            'ALTER TABLE gems__tokens MODIFY COLUMN gto_result varchar(255) DEFAULT NULL',
            'ALTER TABLE gems__tokens MODIFY COLUMN gto_comment text',
        ];
    }
}
