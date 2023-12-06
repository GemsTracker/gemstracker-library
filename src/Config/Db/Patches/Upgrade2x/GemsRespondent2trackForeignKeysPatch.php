<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsRespondent2trackForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__respondent2track';

    private array $foreignKeys = [
        [ 'gr2t_id_user', 'gems__respondents', 'grs_id_user' ],
        [ 'gr2t_id_track', 'gems__tracks', 'gtr_id_track' ],
        [ 'gr2t_id_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'gr2t_mailable', 'gems__mail_codes', 'gmc_id' ],
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
