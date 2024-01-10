<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

/**
 * Remove the foreign key that references gems__staff.gsf_login, because the
 * user login can also be a respondent.
 */
class GemsUserLoginsRemoveForeignKeysPatch extends PatchAbstract
{
    private string $table = 'gems__user_logins';

    private array $foreignKeys = [
        [ 'gul_login', 'gems__staff', 'gsf_login' ],
    ];

    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Remove gul_login foreign key from ' . $this->table;
    }

    public function getOrder(): int
    {
        return 20231204000002;
    }

    public function up(): array
    {
        $statements = [];
        foreach ($this->foreignKeys as $foreignKeyData) {
            list($col, $refTable, $refCol) = $foreignKeyData;
            if ($this->databaseInfo->tableHasForeignKey($this->table, $col, $refTable, $refCol)) {
                $foreignKey = $this->databaseInfo->getForeignKeyName($this->table, $col, $refTable, $refCol);
                $statements[] = sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $this->table, $foreignKey);
            }
        }

        return $statements;
    }
}
