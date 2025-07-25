<?php

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsSystemUserMenuLayoutPatch extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add changing the menu for a embedded system user';
    }

    public function getOrder(): int
    {
        return 2025072401744;
    }

    public function up(): array
    {
        $patches = [];
        if (!$this->databaseInfo->tableHasColumn('gems__systemuser_setup', 'gsus_deferred_menu_layout')) {
            $patches[] = "ALTER TABLE gems__systemuser_setup ADD COLUMN gsus_deferred_menu_layout text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null AFTER gsus_redirect";
        }

        return $patches;
    }



    public function down(): ?array
    {
        $patches = [];
        if ($this->databaseInfo->tableHasColumn('gems__systemuser_setup', 'gsus_deferred_menu_layout')) {
            $patches[] = "ALTER TABLE gems__systemuser_setup DROP COLUMN gsus_deferred_menu_layout text";
        }

        return $patches;
    }
}