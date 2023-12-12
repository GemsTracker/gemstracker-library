<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsStaffForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__staff';

    private array $foreignKeys = [
        [ 'gsf_id_organization', 'gems__organizations', 'gor_id_organization' ],
    ];

    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add foreign keys to ' . $this->table;
    }

    public function getOrder(): int
    {
        return 20231204000001;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__staff MODIFY COLUMN gsf_id_organization bigint unsigned NOT NULL',
        ];
        foreach ($this->foreignKeys as $foreignKeyData) {
            list($col, $refTable, $refCol) = $foreignKeyData;
            if (!$this->databaseInfo->tableHasForeignKey($this->table, $col, $refTable, $refCol)) {
                $statements[] = sprintf('ALTER TABLE %s ADD FOREIGN KEY (%s) REFERENCES %s(%s)', $this->table, $col, $refTable, $refCol);
            }
        }

        return $statements;
    }
}
