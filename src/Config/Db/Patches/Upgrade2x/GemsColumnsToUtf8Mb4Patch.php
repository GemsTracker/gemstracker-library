<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsColumnsToUtf8Mb4Patch extends PatchAbstract
{
    var array $gems_columns;

    public function __construct(
        protected array $config,
    )
    {
        $db = new Adapter($config['db']);
        $resultFetcher = new ResultFetcher($db);
        $this->gems_columns = $resultFetcher->fetchAll('SELECT * FROM information_schema.columns WHERE table_schema = "' . $this->config['db']['database'] . '"');
    }

    public function getDescription(): string|null
    {
        return 'Convert all columns with a specific character set to utf8mb4';
    }

    public function getOrder(): int
    {
        return 20230101000006;
    }

    public function up(): array
    {
        $statements = [];
        foreach ($this->gems_columns as $column) {
            // We only want to convert columns that are char, text or varchar.
            if (!in_array($column['DATA_TYPE'], ['char', 'text', 'varchar'])) {
                continue;
            }
            // If no explicit character set or collation is set, we assume the
            // column is already utf8mb4, because this is the table default.
            if (is_null($column['CHARACTER_SET_NAME']) && is_null($column['COLLATION_NAME'])) {
                continue;
            }
            // If the column is already utf8mb4, we don't need to convert it.
            if ($column['CHARACTER_SET_NAME'] === 'utf8mb4' && $column['COLLATION_NAME'] === 'utf8mb4_unicode_ci') {
                continue;
            }
            // If the collation is utf8mb4_bin, we leave that as is.
            if ($column['CHARACTER_SET_NAME'] === 'utf8mb4' && $column['COLLATION_NAME'] === 'utf8mb4_bin') {
                continue;
            }
            $statements[] = 'ALTER TABLE ' . $column['TABLE_NAME'] . ' CHANGE COLUMN ' . $column['COLUMN_NAME'] . ' ' . $column['COLUMN_NAME'] . ' ' . $column['COLUMN_TYPE'] . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }
        // If no columns need to be converted, we still need to return a statement.
        if (empty($statements)) {
            $statements[] = 'SELECT 1';
        }
        return $statements;
    }
}
