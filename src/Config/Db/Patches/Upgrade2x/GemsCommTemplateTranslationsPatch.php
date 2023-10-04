<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsCommTemplateTranslationsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__comm_template_translations for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__comm_template_translations CHANGE COLUMN gctt_body gctt_body text',
        ];
    }
}
