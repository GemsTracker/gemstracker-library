<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsRespondent2track2appointmentForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__respondent2track2appointment';

    private array $foreignKeys = [
        [ 'gr2t2a_id_respondent_track', 'gems__respondent2track', 'gr2t_id_respondent_track' ],
        [ 'gr2t2a_id_app_field', 'gems__track_appointments', 'gtap_id_app_field' ],
        [ 'gr2t2a_id_appointment', 'gems__appointments', 'gap_id_appointment' ],
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
