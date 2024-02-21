<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsGroupsV2Patch extends PatchAbstract
{
    public function __construct(
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Update gems__groups for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__groups MODIFY COLUMN ggp_allowed_ip_ranges text',
            'ALTER TABLE gems__groups MODIFY COLUMN ggp_no_2factor_ip_ranges text',
            'ALTER TABLE gems__groups MODIFY COLUMN ggp_mask_settings text',
        ];

        $add_key = false;
        if (!$this->databaseInfo->tableHasColumn('gems__groups', 'ggp_code')) {
            $statements[] = 'ALTER TABLE gems__groups ADD COLUMN ggp_code varchar(30) NOT NULL AFTER ggp_id_group';
            $add_key = true;
        }
        if (!$this->databaseInfo->tableHasColumn('gems__groups', 'ggp_member_type')) {
            $statements[] = 'ALTER TABLE gems__groups ADD COLUMN ggp_member_type varchar(30) NOT NULL AFTER ggp_respondent_members';
        }
        $statements[] = 'UPDATE gems__groups SET ggp_code = REGEXP_REPLACE(LOWER(ggp_name), "[^a-z_]", "_") WHERE ggp_code = ""';
        $statements[] = 'UPDATE gems__groups SET ggp_member_type = "staff" WHERE ggp_member_type = "" AND ggp_staff_members = 1 AND ggp_respondent_members = 0';
        $statements[] = 'UPDATE gems__groups SET ggp_member_type = "respondent" WHERE ggp_member_type = "" AND ggp_staff_members = 0 AND ggp_respondent_members = 1';
        if ($add_key) {
            $statements[] = 'ALTER TABLE gems__groups ADD UNIQUE KEY ggp_code (ggp_code)';
        }

        return $statements;
    }
}
