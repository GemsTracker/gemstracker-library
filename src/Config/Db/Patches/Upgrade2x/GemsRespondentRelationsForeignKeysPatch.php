<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsRespondentRelationsForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__respondent_relations';

    private array $foreignKeys = [
        [ 'grr_id_respondent', 'gems__respondents', 'grs_id_user' ],
        [ 'grr_id_staff', 'gems__staff', 'gsf_id_user' ],
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
            'ALTER TABLE gems__respondent_relations MODIFY COLUMN grr_id_respondent bigint unsigned NOT NULL',
            'ALTER TABLE gems__respondent_relations MODIFY COLUMN grr_id_staff bigint unsigned NULL',
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
