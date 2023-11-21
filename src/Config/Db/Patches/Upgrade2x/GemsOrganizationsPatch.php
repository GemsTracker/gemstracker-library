<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsOrganizationsPatch extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Update gems__organizations for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__organizations MODIFY COLUMN gor_accessible_by text',
            'ALTER TABLE gems__organizations MODIFY COLUMN gor_welcome text',
            'ALTER TABLE gems__organizations MODIFY COLUMN gor_signature text',
            'ALTER TABLE gems__organizations MODIFY COLUMN gor_allowed_ip_ranges text',
        ];

        if (!$this->databaseInfo->tableHasColumn('gems__organizations', 'gor_sites')) {
            $statements[] = 'ALTER TABLE gems__organizations ADD COLUMN gor_sites VARCHAR(255) NULL AFTER gor_url';
        }

        return $statements;
    }
}
