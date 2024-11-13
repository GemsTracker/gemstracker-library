<?php

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchInterface;

class GemsSystemUserAllowedIpRangePatch implements PatchInterface
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add allowed IP range to system user';
    }

    public function getOrder(): int
    {
        return 20241113101500;
    }

    public function up(): array
    {
        $patches = [];
        if (!$this->databaseInfo->tableHasColumn('gems__systemuser_setup', 'gsus_allowed_ip_ranges')) {
            $patches[] = "ALTER TABLE gems__systemuser_setup ADD COLUMN gsus_allowed_ip_ranges text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null AFTER gsus_redirect";
        }

        return $patches;
    }



    public function down(): ?array
    {
        if ($this->databaseInfo->tableHasColumn('gems__systemuser_setup', 'gsus_allowed_ip_ranges')) {
            $patches[] = "ALTER TABLE gems__systemuser_setup DROP COLUMN gsus_allowed_ip_ranges text";
        }

        return $patches;
    }
}