<?php

declare(strict_types=1);

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsAddAppVersionFieldToAuditLogPatch extends PatchAbstract
{
    public function __construct(protected readonly DatabaseInfo $databaseInfo)
    {
    }

    public function getOrder(): int
    {
        return 20241002120000;
    }

    public function up(): array
    {
        $statements = [];

        // This may need to fit long versions, which in some cases contain two
        // commit ids, so we need a relatively long column.
        if (!$this->databaseInfo->tableHasColumn('gems__log_setup', 'gls_app_version')) {
            $statements[] =
                "ALTER TABLE `gems__log_setup` ADD `gls_app_version` varchar(100) NULL DEFAULT NULL AFTER `gls_on_change`";
        }

        return $statements;
    }

    public function down(): ?array
    {
        $statements = [];

        if ($this->databaseInfo->tableHasColumn('gems__log_setup', 'gls_app_version')) {
            $statements[] = "ALTER TABLE `gems__log_setup` DROP `gls_app_version`;";
        }

        return $statements;
    }
}
