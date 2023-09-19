<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsMailServersPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__mail_servers for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__mail_servers DROP PRIMARY KEY',
            'ALTER TABLE gems__mail_servers ADD COLUMN gms_id_server bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
            'ALTER TABLE gems__mail_servers ADD UNIQUE KEY gms_from (gms_from)',
        ];
    }
}
