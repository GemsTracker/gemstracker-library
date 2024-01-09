<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsLogRespondentConsentsForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__log_respondent_consents';

    private array $foreignKeys = [
        [ 'glrc_id_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'glrc_id_user', 'gems__respondents', 'grs_id_user' ],
        [ 'glrc_old_consent', 'gems__consents', 'gco_description' ],
        [ 'glrc_new_consent', 'gems__consents', 'gco_description' ],
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
            'UPDATE gems__log_respondent_consents SET glrc_old_consent=null WHERE glrc_old_consent=""',
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
