<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsEpisodesOfCarePatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Update gems__episodes_of_care for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__episodes_of_care MODIFY COLUMN gec_comment text',
            'ALTER TABLE gems__episodes_of_care MODIFY COLUMN gec_diagnosis_data text',
            'ALTER TABLE gems__episodes_of_care MODIFY COLUMN gec_extra_data text',
        ];

        if ($this->databaseInfo->tableHasConstraint('gems__episodes_of_care', 'gec_id_in_source_gec_id_organization_gec_source')) {
            $statements[] = 'ALTER TABLE gems__episodes_of_care DROP KEY gec_id_in_source_gec_id_organization_gec_source';
        }

        return $statements;
    }
}
