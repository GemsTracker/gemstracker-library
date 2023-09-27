<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTrackFieldsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__track_fields for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__track_fields MODIFY COLUMN gtf_field_values TEXT',
            'ALTER TABLE gems__track_fields MODIFY COLUMN gtf_field_value_keys TEXT',
        ];
    }
}
