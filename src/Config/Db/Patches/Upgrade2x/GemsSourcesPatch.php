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
            "ALTER TABLE gems__sources MODIFY COLUMN gso_ls_class varchar(60) NOT NULL default 'Gems\\Tracker\\Source\\LimeSurvey3m00Database'",
            "UPDATE gems__sources SET gso_ls_class = 'LimeSurvey3m00Database' WHERE gso_ls_class IN ('LimeSurvey1m9Database', 'LimeSurvey1m91Database', 'LimeSurvey2m00Database')",
            "UPDATE gems__sources SET gso_ls_class = 'LimeSurvey5m00Database' WHERE gso_ls_class = 'LimeSurvey4m00Database'",
        ];
    }
}
