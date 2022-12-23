<?php

namespace Gems\Config;

use Gems\Api\RestModelConfigProviderAbstract;
use Gems\Model\CommTemplateModel;
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
            'comm-template' => [
                'model' => CommTemplateModel::class,
                'constructor' => true,
                'methods' => ['GET', 'POST'],
                'allowed_fields' => [
                    'id',
                    'gct_id_template',
                    'name',
                    'gct_name',
                    'code',
                    'gct_code',
                    'mailTarget',
                    'gct_target',
                    'translations' => [
                        'language',
                        'gctt_lang',
                        'subject',
                        'gct_subject',
                        'body',
                        'gct_body',
                    ],
                ],
            ]
        ];
    }
}