<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;

class GemsTablesEncryptionPatch extends PatchAbstract
{
    var array $gems_tables;
    protected string $default_encryption = '';

    public function __construct(
        protected array $config,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
    }

    protected function prepare(): void
    {
        $this->gems_tables = $this->resultFetcher->fetchAll('SELECT * FROM information_schema.tables WHERE table_schema = "' . $this->config['db']['database'] . '"');
        $default_encryption = $this->resultFetcher->fetchOne('SELECT DEFAULT_ENCRYPTION FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "' . $this->config['db']['database'] . '"');
        if ($default_encryption) {
            $this->default_encryption = $default_encryption;
        }
    }

    public function getDescription(): string|null
    {
        return 'Encrypt all tables if default encryption is enabled';
    }

    public function getOrder(): int
    {
        return 20231205000001;
    }

    public function up(): array
    {
        $this->prepare();

        $statements = [];
        // If default encryption is disabled, we don't need to do anything.
        if ($this->default_encryption === 'NO') {
            return $statements;
        }
        foreach ($this->gems_tables as $table) {
            // If the table is already encrypted, we don't need to convert it.
            if (preg_match('/ENCRYPTION=.Y./', $table['CREATE_OPTIONS'])) {
                continue;
            }
            $statements[] = 'ALTER TABLE ' . $table['TABLE_NAME'] . " ENCRYPTION = 'Y'";
        }

        return $statements;
    }
}
