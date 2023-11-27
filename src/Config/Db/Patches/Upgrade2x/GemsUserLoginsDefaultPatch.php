<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsUserLoginsDefaultPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__user_logins for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000001;
    }

    public function up(): array
    {
        return [
            "UPDATE gems__user_logins SET gul_enable_2factor = 0 WHERE gul_enable_2factor IS NULL",
        ];
    }
}
