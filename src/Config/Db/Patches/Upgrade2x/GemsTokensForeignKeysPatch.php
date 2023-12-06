<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsTokensForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__tokens';

    private array $foreignKeys = [
        [ 'gto_id_respondent_track', 'gems__respondent2track', 'gr2t_id_respondent_track' ],
        [ 'gto_id_round', 'gems__rounds', 'gro_id_round' ],
        [ 'gto_id_respondent', 'gems__respondents', 'grs_id_user' ],
        [ 'gto_id_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'gto_id_track', 'gems__tracks', 'gtr_id_track' ],
        [ 'gto_id_survey', 'gems__surveys', 'gsu_id_survey' ],
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
