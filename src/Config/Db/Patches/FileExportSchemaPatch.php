<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config\Db\Patches
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

/**
 * @package    Gems
 * @subpackage Config\Db\Patches
 * @since      Class available since version 1.0
 */
class FileExportSchemaPatch extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add gfex_schema_name to gems__file_exports';
    }

    public function getOrder(): int
    {
        return 20250710000001;
    }

    public function down(): array
    {
        if ($this->databaseInfo->tableHasColumn('gems__file_exports', 'gfex_schema_name')) {
            return ["ALTER TABLE gems__file_exports DROP COLUMN gfex_schema_name;"];
        }

        return [];
    }

    public function up(): array
    {
        if (!$this->databaseInfo->tableHasColumn('gems__file_exports', 'gfex_schema_name')) {
            return ["ALTER TABLE gems__file_exports ADD gfex_schema_name varchar(128) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null AFTER gfex_file_name;"];
        }

        return [];
    }
}