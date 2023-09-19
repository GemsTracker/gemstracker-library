<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsSurveyQuestionsPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__survey_questions for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230919000000;
    }

    public function up(): array
    {
        return [
            'ALTER TABLE gems__survey_questions MODIFY COLUMN gsq_label TEXT',
            'ALTER TABLE gems__survey_questions MODIFY COLUMN gsq_description TEXT',
            'ALTER TABLE gems__survey_questions MODIFY COLUMN gsq_name varchar(100) NOT NULL',
        ];
    }
}
