<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsSourcesPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__sources for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            "ALTER TABLE gems__sources MODIFY COLUMN gso_ls_class varchar(60) NOT NULL default 'Gems_Source_LimeSurvey3m00Database'",
        ];
    }
}
