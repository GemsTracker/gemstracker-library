<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsLogRespondent2track2fieldPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__log_respondent2track2field for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__log_respondent2track2field MODIFY COLUMN glrtf_old_value text',
            'ALTER TABLE gems__log_respondent2track2field MODIFY COLUMN glrtf_new_value text',
        ];
    }
}
