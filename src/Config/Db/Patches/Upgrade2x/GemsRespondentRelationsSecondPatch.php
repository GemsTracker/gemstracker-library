<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsRespondentRelationsSecondPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__respondent_relations for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000102;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__respondent_relations ADD KEY grr_id_respondent (grr_id_respondent, grr_id_staff)',
        ];
    }
}
