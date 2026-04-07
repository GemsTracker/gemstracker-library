<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class AddDiffTargetFieldToTrackAppointments extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {  }

    public function getDescription(): string|null
    {
        return 'Add diff target field to track appointments';
    }

    public function getOrder(): int
    {
        return 20250917100000;
    }

    public function up(): array
    {
        $statements = [];
        if (!$this->databaseInfo->tableHasColumn('gems__track_appointments', 'gtap_diff_target_field')) {
            $statements[] = 'ALTER TABLE gems__track_appointments ADD COLUMN gtap_diff_target_field varchar(20) null AFTER gtap_filter_id';
        }
        return $statements;
    }

    public function down(): array
    {
        return [
            'ÁLTER TABLE gems__track_appointments DROP COLUMN gtap_diff_target_field',
        ];
    }
}
