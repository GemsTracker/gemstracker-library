<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsAppointmentsForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__appointments';

    private array $foreignKeys = [
        [ 'gap_id_user', 'gems__respondents', 'grs_id_user' ],
        [ 'gap_id_organization', 'gems__organizations', 'gor_id_organization' ],
        [ 'gap_id_episode', 'gems__episodes_of_care', 'gec_episode_of_care_id' ],
        [ 'gap_id_attended_by', 'gems__agenda_staff', 'gas_id_staff' ],
        [ 'gap_id_referred_by', 'gems__agenda_staff', 'gas_id_staff' ],
        [ 'gap_id_activity', 'gems__agenda_activities', 'gaa_id_activity' ],
        [ 'gap_id_procedure', 'gems__agenda_procedures', 'gapr_id_procedure' ],
        [ 'gap_id_location', 'gems__locations', 'glo_id_location' ],
        [ 'gap_diagnosis_code', 'gems__agenda_diagnoses', 'gad_diagnosis_code' ],
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
