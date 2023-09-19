<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsEpisodesOfCarePatch extends PatchAbstract
{
    var array $gems_table_constraints;

    public function __construct(
        protected array $config,
    )
    {
        $db = new Adapter($config['db']);
        $resultFetcher = new ResultFetcher($db);
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], 'gems__episodes_of_care');
        $this->gems_table_constraints = $resultFetcher->fetchAll($sql);
    }

    public function getDescription(): string|null
    {
        return 'Update gems__episodes_of_care for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__episodes_of_care MODIFY COLUMN gec_comment text',
            'ALTER TABLE gems__episodes_of_care MODIFY COLUMN gec_diagnosis_data text',
            'ALTER TABLE gems__episodes_of_care MODIFY COLUMN gec_extra_data text',
        ];
        // Check if the key we want to drop exists.
        // If it does, we need to drop it.
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'gec_id_in_source_gec_id_organization_gec_source') {
                $statements[] = 'ALTER TABLE gems__episodes_of_care DROP KEY gec_id_in_source_gec_id_organization_gec_source';
                break;
            }
        }

        return $statements;
    }
}
