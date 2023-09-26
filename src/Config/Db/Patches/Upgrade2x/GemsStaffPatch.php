<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsStaffPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__staff for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            "ALTER TABLE gems__staff MODIFY COLUMN gsf_active tinyint(1) NULL DEFAULT '1'",
        ];
    }
}
