<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsLogRespondentCommunicationsForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__log_respondent_communications';

    private array $foreignKeys = [
        [ 'grco_id_to', 'gems__respondents', 'grs_id_user' ],
        [ 'grco_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'grco_id_token', 'gems__tokens', 'gto_id_token' ],
        [ 'grco_id_message', 'gems__comm_templates', 'gct_id_template' ],
        [ 'grco_id_job', 'gems__comm_jobs', 'gcj_id_job' ],
        [ 'grco_id_by', 'gems__staff', 'gsf_id_user', ],
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
