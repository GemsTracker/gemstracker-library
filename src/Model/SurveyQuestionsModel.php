<?php

namespace Gems\Model;

use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class SurveyQuestionsModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate
    ) {
        parent::__construct('gems__survey_questions', $metaModelLoader, $sqlRunner, $translate);
        $metaModelLoader->setChangeFields($this->metaModel, 'gsq');
    }
}