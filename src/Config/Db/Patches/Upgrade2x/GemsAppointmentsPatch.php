<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsAppointmentsPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Update gems__appointments for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [];
        if (!$this->databaseInfo->tableHasColumn('gems__appointments', 'gap_last_synch')) {
            $statements[] = 'ALTER TABLE gems__appointments ADD COLUMN gap_last_synch timestamp NULL DEFAULT NULL AFTER gap_id_in_source';
        }
        $statements[] = 'ALTER TABLE gems__appointments MODIFY COLUMN gap_id_in_source varchar(40) DEFAULT NULL';
        $statements[] = 'ALTER TABLE gems__appointments MODIFY COLUMN gap_comment text';

        if ($this->databaseInfo->tableHasConstraint('gems__appointments', 'gap_changed')) {
            $statements[] = 'ALTER TABLE gems__appointments DROP KEY gap_changed';
        }

        return $statements;
    }
}
