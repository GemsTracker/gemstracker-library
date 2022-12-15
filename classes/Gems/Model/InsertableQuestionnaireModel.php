<?php

namespace Gems\Model;

use Gems\Api\Fhir\Model\QuestionnaireModel;
use Gems\Model\Transform\QuestionnaireInsertableOrganizationTransformer;

class InsertableQuestionnaireModel extends QuestionnaireModel
{
    public function __construct()
    {
        parent::__construct();
        $this->set('organization', [
            'apiName' => 'organization',
        ]);
    }

    public function afterRegistry()
    {
        parent::afterRegistry();
        $this->addTransformer(new QuestionnaireInsertableOrganizationTransformer());
    }
}