<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsTimestampDefaultPatch extends PatchAbstract
{
    var array $gems_tables;
    var array $gems_columns;

    public function __construct(
        protected array $config,
    )
    {
    }

    protected function prepare(): void
    {
        $db = new Adapter($this->config['db']);
        $resultFetcher = new ResultFetcher($db);
        $this->gems_tables = $resultFetcher->fetchAll('SELECT * FROM information_schema.tables WHERE table_schema = "' . $this->config['db']['database'] . '"');
        $this->gems_columns = $resultFetcher->fetchAll('SELECT * FROM information_schema.columns WHERE table_schema = "' . $this->config['db']['database'] . '"');
    }

    public function getDescription(): string|null
    {
        // Note: this resets any ON UPDATE statements!
        return 'Change all created, _opened and _changed columns with an invalid default to CURRENT_TIMESTAMP';
    }

    public function getOrder(): int
    {
        return 20230101000001;
    }

    public function up(): array
    {
        $this->prepare();

        $statements = [];
        foreach ($this->gems_tables as $table) {
            $modify_columns = [];
            foreach ($this->gems_columns as $column) {
                // Find only columns in this table.
                if ($column['TABLE_NAME'] != $table['TABLE_NAME']) {
                    continue;
                }
                // We only want to convert timestamp columns.
                if ($column['DATA_TYPE'] != 'timestamp') {
                    continue;
                }
                // We only want to change the default if it is the invalue
                // value of 0000-00-00 00:00:00.
                if ($column['COLUMN_DEFAULT'] != '0000-00-00 00:00:00') {
                    continue;
                }
                // We only want to update the default of _created, _opened and _changed columns.
                // Note that some columns are just called 'created'.
                if (!preg_match('/(created|_opened|_changed?)$/', $column['COLUMN_NAME'])) {
                    continue;
                }
                $modify_columns[] = $column;
            }
            if (empty($modify_columns)) {
                continue;
            }
            $column = $modify_columns[0];
            $statement = 'ALTER TABLE ' . $column['TABLE_NAME'] . ' MODIFY COLUMN ' . $column['COLUMN_NAME'] . ' ' . $column['COLUMN_TYPE'] . ' NOT NULL DEFAULT CURRENT_TIMESTAMP';
            // Take off the first (possibly the only) column.
            array_shift($modify_columns);
            // And add the rest to the statement.
            foreach ($modify_columns as $column) {
                $statement .= ', MODIFY COLUMN ' . $column['COLUMN_NAME'] . ' ' . $column['COLUMN_TYPE'] . ' NOT NULL DEFAULT CURRENT_TIMESTAMP';
            }
            $statements[] = $statement;
        }
        // If no columns need to be converted, we still need to return a statement.
        if (empty($statements)) {
            $statements[] = 'SELECT 1';
        }
        return $statements;
    }
}
