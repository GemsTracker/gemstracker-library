<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsAppointmentsPatch extends PatchAbstract
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
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], 'gems__appointments');
        $this->gems_table_constraints = $this->resultFetcher->fetchAll($sql) ?? [];
    }

    public function getDescription(): string|null
    {
        return 'Update gems__appointments for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $this->prepare();

        $statements = [
            'ALTER TABLE gems__appointments ADD COLUMN gap_last_synch timestamp NULL DEFAULT NULL AFTER gap_id_in_source',
            'ALTER TABLE gems__appointments MODIFY COLUMN gap_id_in_source varchar(40) DEFAULT NULL',
            'ALTER TABLE gems__appointments MODIFY COLUMN gap_comment text',

        ];
        // Check if the key we want to drop exists.
        // If it does, we need to drop it.
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'gap_changed') {
                $statements[] = 'ALTER TABLE gems__appointments DROP KEY gap_changed';
                break;
            }
        }

        return $statements;
    }
}
