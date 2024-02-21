<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsRespondentsPatch extends PatchAbstract
{
    var array $gems_table_constraints;

    public function __construct(
        protected array $config,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
    }

    protected function prepare(): void
    {
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], 'gems__respondents');
        $this->gems_table_constraints = $this->resultFetcher->fetchAll($sql);
    }

    public function getDescription(): string|null
    {
        return 'Update gems__respondents for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $this->prepare();

        $statements = [
            'ALTER TABLE gems__respondents MODIFY COLUMN grs_id_user bigint unsigned NOT NULL',
        ];
        // Check if the key we want to drop exists.
        // If it does, we need to drop it.
        $add_ssn_key = true;
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'grs_bsn') {
                $statements[] = 'ALTER TABLE gems__respondents DROP KEY grs_bsn';
                continue;
            }
            if ($constraint['CONSTRAINT_NAME'] === 'grs_ssn') {
                $add_ssn_key = false;
                continue;
            }
        }
        if ($add_ssn_key) {
            $statements[] = 'ALTER TABLE gems__respondents ADD UNIQUE KEY grs_ssn (grs_ssn)';
        }

        return $statements;
    }
}