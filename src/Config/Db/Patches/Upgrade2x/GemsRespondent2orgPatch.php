<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsRespondent2orgPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__respondent2org for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__respondent2org MODIFY COLUMN gr2o_comments TEXT',
            'ALTER TABLE gems__respondent2org MODIFY COLUMN gr2o_opened timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ];
    }
}
