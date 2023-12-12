<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsSurveyIdBigintPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Ensure columns having a survey id are always of type bigint';
    }

    public function getOrder(): int
    {
        return 20231203000001;
    }

    public function up(): array
    {
        $statements = [
            'ALTER TABLE gems__comm_jobs MODIFY COLUMN gcj_id_survey bigint unsigned null',
            'ALTER TABLE gems__survey_question_options MODIFY COLUMN gsqo_id_survey bigint unsigned not null',
            'ALTER TABLE gems__survey_questions MODIFY COLUMN gsq_id_survey bigint unsigned not null',
            'ALTER TABLE gems__surveys MODIFY COLUMN gsu_id_survey bigint unsigned not null auto_increment',
        ];

        return $statements;
    }
}
