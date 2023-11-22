<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsTrackFieldsPatch extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

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
        $statements = ['ALTER TABLE gems__track_fields MODIFY COLUMN gtf_field_values TEXT'];

        if ($this->databaseInfo->tableHasColumn('gems__track_fields', 'gtf_field_value_keys')) {
            $statements[] = 'ALTER TABLE gems__track_fields MODIFY COLUMN gtf_field_value_keys TEXT';
        } else {
            $statements[] = 'ALTER TABLE gems__track_fields ADD COLUMN gtf_field_value_keys TEXT AFTER gtf_field_description';
        }

        return $statements;
    }
}
