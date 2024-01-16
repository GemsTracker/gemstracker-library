<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsRoleNamesPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update role names for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000201;
    }

    public function up(): array
    {
        return [
            'UPDATE gems__roles SET grl_name=LOWER(REPLACE(REPLACE(grl_name,"_",""),"-",""))',
            'UPDATE gems__groups SET ggp_role=LOWER(REPLACE(REPLACE(ggp_role,"_",""),"-",""))',
            'UPDATE gems__groups INNER JOIN gems__roles ON ggp_role=grl_id_role SET ggp_role=grl_name',
        ];
    }
}
