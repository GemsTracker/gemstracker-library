<?php

declare(strict_types=1);

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsExportIdLengthPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Lengthen gems__file_exports gfex_export_id field to 100 characters';
    }

    public function getOrder(): int
    {
        return 20251407125616;
    }

    public function up(): array
    {
        return ["ALTER TABLE gems__file_exports CHANGE gfex_export_id gfex_export_id varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL"];
    }

    public function down(): ?array
    {
        return ["ALTER TABLE gems__file_exports CHANGE gfex_export_id gfex_export_id varchar(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL"];
    }
}
