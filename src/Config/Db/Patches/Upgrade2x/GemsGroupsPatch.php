<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsGroupsPatch extends PatchAbstract
{
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
        return [
            'ALTER TABLE gems__groups MODIFY COLUMN ggp_allowed_ip_ranges text',
            'ALTER TABLE gems__groups MODIFY COLUMN ggp_no_2factor_ip_ranges text',
            'ALTER TABLE gems__groups MODIFY COLUMN ggp_mask_settings text',
        ];
    }
}
