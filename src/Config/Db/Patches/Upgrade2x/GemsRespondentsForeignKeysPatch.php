<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsRespondentsForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__respondents';

    private array $foreignKeys = [
        [ 'grs_id_user', 'gems__user_ids', 'gui_id_user' ],
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
            'INSERT INTO gems__user_ids (gui_id_user) SELECT grs_id_user FROM gems__respondents WHERE grs_id_user NOT IN (SELECT gui_id_user FROM gems__user_ids)',
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
