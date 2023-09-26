<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsLogActivityPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__log_activity for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__log_activity MODIFY COLUMN gla_message text',
            'ALTER TABLE gems__log_activity MODIFY COLUMN gla_data text',
            'ALTER TABLE gems__log_activity MODIFY COLUMN gla_remote_ip varchar(64) NOT NULL',
        ];
    }
}
