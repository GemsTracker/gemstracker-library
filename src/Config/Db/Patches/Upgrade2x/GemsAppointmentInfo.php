<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchInterface;

class GemsAppointmentInfo implements PatchInterface
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add gap_info field to gems__appointments';
    }

    public function getOrder(): int
    {
        return 20231012162100;
    }

    public function up(): array
    {
        $patches = [];
        if (!$this->databaseInfo->tableHasColumn('gems__appointments', 'gap_info')) {
            $patches[] = 'ALTER TABLE gems__appointments ADD COLUMN gap_info json NULL AFTER gap_comment';
        }

        return $patches;
    }

    public function down(): ?array
    {
        return null;
    }
}