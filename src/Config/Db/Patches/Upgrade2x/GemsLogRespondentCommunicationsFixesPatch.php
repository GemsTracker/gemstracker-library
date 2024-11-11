<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsLogRespondentCommunicationsFixesPatch extends PatchAbstract
{
    private string $table = 'gems__log_respondent_communications';

    private array $foreignKeys = [
        [ 'grco_id_by', 'gems__staff', 'gsf_id_user', ],
    ];

    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Remove foreign keys from ' . $this->table;
    }

    public function getOrder(): int
    {
        return 20231204000002;
    }

    public function up(): array
    {
        $statements[] = 'ALTER TABLE gems__log_respondent_communications MODIFY COLUMN grco_changed timestamp not null default current_timestamp on update current_timestamp';

        foreach ($this->foreignKeys as $foreignKeyData) {
            list($col, $refTable, $refCol) = $foreignKeyData;
            if ($this->databaseInfo->tableHasForeignKey($this->table, $col, $refTable, $refCol)) {
                $foreignKey = $this->databaseInfo->getForeignKeyName($this->table, $col, $refTable, $refCol);
                $statements[] = sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $this->table, $foreignKey);
            }
        }
        if (! $this->databaseInfo->tableHasConstraint('gems__log_respondent_communications', 'grco_id_by')) {
            $statements[] = 'ALTER TABLE gems__log_respondent_communications ADD KEY grco_id_by (grco_id_by)';
        }

        return $statements;
    }
}
