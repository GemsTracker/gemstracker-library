<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsSurveysPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__surveys for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__surveys MODIFY COLUMN gsu_mail_code tinyint NOT NULL DEFAULT "1"',
            'ALTER TABLE gems__surveys ADD COLUMN gsu_answers_by_group tinyint(1) NOT NULL DEFAULT "0" AFTER gsu_id_primary_group',
            'ALTER TABLE gems__surveys ADD COLUMN gsu_answer_groups varchar(250) AFTER gsu_answers_by_group',
            'ALTER TABLE gems__surveys ADD COLUMN gsu_allow_export tinyint(1) NOT NULL DEFAULT "1" AFTER gsu_answer_groups',
        ];
    }
}
