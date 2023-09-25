<?php

namespace Gems\Model;

use Gems\Api\Fhir\Model\QuestionnaireModel;
use Gems\Locale\Locale;
use Gems\Model\Transform\QuestionnaireInsertableOrganizationTransformer;
use Gems\Tracker;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class InsertableQuestionnaireModel extends QuestionnaireModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        Tracker $tracker,
        Locale $locale,
    )
    {
        parent::__construct($metaModelLoader, $sqlRunner, $translate, $tracker, $locale);
        $this->metaModel->set('organization', [
            'apiName' => 'organization',
        ]);

        $this->metaModel->addTransformer(new QuestionnaireInsertableOrganizationTransformer());
    }
}