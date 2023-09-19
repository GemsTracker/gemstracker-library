<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsChartConfigPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__chart_config for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__chart_config MODIFY COLUMN gcc_config text DEFAULT NULL',
        ];
    }
}
