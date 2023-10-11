<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsUserLoginsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__user_logins for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            "UPDATE gems__user_logins SET gul_enable_2factor = 0 WHERE gul_enable_2factor IS NULL",
            "ALTER TABLE gems__user_logins MODIFY COLUMN gul_enable_2factor tinyint(1) NOT NULL DEFAULT '1'",
            'ALTER TABLE gems__user_logins ADD COLUMN gul_session_key varchar(32) DEFAULT NULL AFTER gul_can_login',
        ];
    }
}
