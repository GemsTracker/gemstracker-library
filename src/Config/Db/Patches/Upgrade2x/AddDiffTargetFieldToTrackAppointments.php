<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class AddDiffTargetFieldToTrackAppointments extends PatchAbstract
{
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
        return [
            'ALTER TABLE gems__track_appointments ADD COLUMN gtap_diff_target_field varchar(20) null AFTER gtap_filter_id',
        ];
    }

    public function down(): array
    {
        return [
            'ÁLTER TABLE gems__track_appointments DROP COLUMN gtap_diff_target_field',
        ];
    }
}