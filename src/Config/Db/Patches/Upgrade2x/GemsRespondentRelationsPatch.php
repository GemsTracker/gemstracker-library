<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsRespondentRelationsPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Update gems__respondent_relations for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000101;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__respondent_relations MODIFY COLUMN grr_comments TEXT',
            'ALTER TABLE gems__respondent_relations ADD KEY temp_grr_id_respondent (grr_id_respondent)', // For foreign key
        ];

        if ($this->databaseInfo->tableHasConstraint('gems__respondent_relations', 'grr_id_respondent_staff')) {
            $statements[] = 'ALTER TABLE gems__respondent_relations DROP KEY grr_id_respondent_staff';
        }
        if ($this->databaseInfo->tableHasConstraint('gems__respondent_relations', 'grr_id_respondent')) {
            $statements[] = 'ALTER TABLE gems__respondent_relations DROP KEY grr_id_respondent';
        }

        $statements[] = 'ALTER TABLE gems__respondent_relations ADD KEY grr_id_respondent_staff (grr_id_respondent, grr_id_staff)';
        $statements[] = 'ALTER TABLE gems__respondent_relations DROP KEY temp_grr_id_respondent';

        return $statements;
    }
}
