<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsTranslationsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__translations for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__translations MODIFY COLUMN gtrs_translation TEXT',
            'ALTER TABLE gems__translations MODIFY COLUMN gtrs_changed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ];
    }
}
