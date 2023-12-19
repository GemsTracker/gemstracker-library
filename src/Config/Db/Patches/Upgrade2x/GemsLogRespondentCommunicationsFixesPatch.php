<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;

class GemsLogRespondentCommunicationsFixesPatch extends PatchAbstract
{
    private string $table = 'gems__log_respondent_communications';
    private array $gems_table_constraints = [];

    private array $foreignKeys = [
        [ 'grco_id_by', 'gems__staff', 'gsf_id_user', ],
    ];

    public function __construct(
        protected array $config,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], $this->table);
        $this->gems_table_constraints = $this->resultFetcher->fetchAll($sql);
    }

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
        $statements = [
            'ALTER TABLE gems__log_respondent_communications MODIFY COLUMN grco_changed timestamp not null default current_timestamp on update current_timestamp',
        ];
        foreach ($this->foreignKeys as $foreignKeyData) {
            list($col, $refTable, $refCol) = $foreignKeyData;
            if ($this->databaseInfo->tableHasForeignKey($this->table, $col, $refTable, $refCol)) {
                $foreignKey = $this->databaseInfo->getForeignKeyName($this->table, $col, $refTable, $refCol);
                $statements[] = sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $this->table, $foreignKey);
            }
        }
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'grco_id_by') {
                return $statements;
            }
        }
        $statements[] = 'ALTER TABLE gems__log_respondent_communications ADD KEY grco_id_by (grco_id_by)';

        return $statements;
    }
}
