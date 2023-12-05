<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsCommJobsForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__comm_jobs';

    private array $foreignKeys = [
        [ 'gcj_id_communication_messenger', 'gems__comm_messengers', 'gcm_id_messenger' ],
        [ 'gcj_id_message', 'gems__comm_templates', 'gct_id_template' ],
        [ 'gcj_id_user_as', 'gems__staff', 'gsf_id_user' ],
        [ 'gcj_id_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'gcj_id_track', 'gems__tracks', 'gtr_id_track' ],
        [ 'gcj_id_survey', 'gems__surveys', 'gsu_id_survey' ],
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
