<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsRespondent2track2fieldPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__respondent2track2field for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__respondent2track2field MODIFY COLUMN gr2t2f_value TEXT',
        ];
    }
}
