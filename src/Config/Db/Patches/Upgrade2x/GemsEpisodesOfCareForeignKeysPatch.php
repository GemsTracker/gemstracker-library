<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsEpisodesOfCareForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__episodes_of_care';

    private array $foreignKeys = [
        [ 'gec_id_user', 'gems__respondents', 'grs_id_user' ],
        [ 'gec_id_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'gec_id_attended_by', 'gems__agenda_staff', 'gas_id_staff' ],
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
        $statements = [];
        foreach ($this->foreignKeys as $foreignKeyData) {
            list($col, $refTable, $refCol) = $foreignKeyData;
            if (!$this->databaseInfo->tableHasForeignKey($this->table, $col, $refTable, $refCol)) {
                $statements[] = sprintf('ALTER TABLE %s ADD FOREIGN KEY (%s) REFERENCES %s(%s)', $this->table, $col, $refTable, $refCol);
            }
        }

        return $statements;
    }
}
