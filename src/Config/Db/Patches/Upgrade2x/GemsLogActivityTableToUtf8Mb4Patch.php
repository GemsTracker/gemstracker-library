<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsLogActivityTableToUtf8Mb4Patch extends PatchAbstract
{
    var array $gems_tables;

    public function __construct(
        protected array $config,
    )
    {
    }

    protected function prepare(): void
    {
        $db = new Adapter($this->config['db']);
        $resultFetcher = new ResultFetcher($db);
        $this->gems_tables = $resultFetcher->fetchAll('SELECT * FROM information_schema.tables WHERE table_schema = "' . $this->config['db']['database'] . '"') ?? [];
    }

    public function getDescription(): string|null
    {
        return 'Convert log_activity table to utf8mb4';
    }

    public function getOrder(): int
    {
        return 20230101000014;
    }

    public function up(): array
    {
        $this->prepare();

        $statements = [];
        foreach ($this->gems_tables as $table) {
            // Only convert the log_activity table.
            if (!str_ends_with($table['TABLE_NAME'], '_log_activity')) {
                continue;
            }
            // If the table is already utf8mb4, we don't need to convert it.
            if ($table['TABLE_COLLATION'] === 'utf8mb4_unicode_ci') {
                continue;
            }
            $statements[] = 'ALTER TABLE ' . $table['TABLE_NAME'] . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }

        return $statements;
    }
}
