<?php

namespace Gems\Config;

use Gems\Api\RestModelConfigProviderAbstract;
use Gems\Model\InsertableQuestionnaireModel;
use Gems\Model\SimpleTrackModel;

class ApiRoutes extends RestModelConfigProviderAbstract
{

    public function __invoke()
    {
        return [
            ...$this->getRoutes(),
        ];
    }

    public function getRestModels(): array
    {
        return [
            'tracks' => [
                'model' => SimpleTrackModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'name',
                    'start',
                    'end',
                    'active',
                    'valid',
                    'organization',
                ],
                'idField' => 'id',
            ],
            'insertable-questionnaire' => [
                'model' => InsertableQuestionnaireModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'resourceType',
                    'id',
                    'status',
                    'name',
                    'date',
                    'description',
                    'subjectType',
                    'item',
                    'organization',
                ],
            ],
        ];
    }
}