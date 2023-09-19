<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTrackAppointmentsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__track_appointments for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            "ALTER TABLE gems__track_appointments MODIFY COLUMN gtap_create_track int NOT NULL DEFAULT '0' AFTER gtap_uniqueness",
            "ALTER TABLE gems__track_appointments MODIFY COLUMN gtap_create_wait_days bigint NOT NULL DEFAULT '182' AFTER gtap_create_track",
        ];
    }
}
