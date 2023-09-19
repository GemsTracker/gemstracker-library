<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsUserIdsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__user_ids for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__user_ids MODIFY COLUMN gui_created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ];
    }
}
