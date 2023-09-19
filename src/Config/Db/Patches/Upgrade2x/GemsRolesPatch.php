<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsRolesPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__roles for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__roles MODIFY COLUMN grl_parents TEXT',
            'ALTER TABLE gems__roles MODIFY COLUMN grl_privileges TEXT',
        ];
    }
}
