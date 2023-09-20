<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsLogSetupPatch extends PatchAbstract
{
    var array $gems_table_constraints;

    public function __construct(
        protected array $config,
    )
    {
        $db = new Adapter($config['db']);
        $resultFetcher = new ResultFetcher($db);
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], 'gems__log_setup');
        $this->gems_table_constraints = $resultFetcher->fetchAll($sql);
    }

    public function getDescription(): string|null
    {
        return 'Update gems__log_setup for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        $statements = [];
        // Check if the key we want to drop exists.
        // If it does, we need to drop it.
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'gls_name_2') {
                $statements[] = 'ALTER TABLE gems__log_setup DROP KEY gls_name_2';
                continue;
            }
            if ($constraint['CONSTRAINT_NAME'] === 'gls_name_3') {
                $statements[] = 'ALTER TABLE gems__log_setup DROP KEY gls_name_3';
                continue;
            }
        }
        // We always need to return a statement.
        if (empty($statements)) {
            $statements[] = 'SELECT 1';
        }

        return $statements;
    }
}