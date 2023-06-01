<?php

namespace Gems\Config;

use Gems\Api\RestModelConfigProviderAbstract;
use Gems\Handlers\Api\CommFieldsHandler;
use Gems\Handlers\Api\Respondent\OtherPatientNumbersHandler;
use Gems\Model\CommTemplateModel;
use Gems\Model\InsertableQuestionnaireModel;
use Gems\Model\SimpleTrackModel;

class ApiRoutes extends RestModelConfigProviderAbstract
{

    public function __invoke()
    {
        return $this->routeGroup(
            [
                'path' => $this->pathPrefix,
                'middleware' => $this->getMiddleware(),
            ],
            $this->getRoutes()
        );
    }

    public function getRoutes(): array
    {
        return [
            ...$this->createModelRoute(
                endpoint: 'tracks',
                model: SimpleTrackModel::class,
                methods: ['GET'],
                allowedFields: [
                    'id',
                    'name',
                    'start',
                    'end',
                    'active',
                    'valid',
                    'organization',
                ],
                idField: 'id',
            ),
            ...$this->createModelRoute(
                endpoint: 'insertable-questionnaire',
                model: InsertableQuestionnaireModel::class,
                methods: ['GET'],
                allowedFields: [
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
            ),
            ...$this->createModelRoute(
                endpoint: 'comm-template',
                model: CommTemplateModel::class,
                methods: ['GET', 'POST', 'PATCH'],
                allowedFields: [
                    'id',
                    'name',
                    'code',
                    'mailTarget',
                    'translations' => [
                        'language',
                        'subject',
                        'body',
                    ],
                ],
                allowedSaveFields: [
                    'gct_id_template',
                    'gct_name',
                    'gct_code',
                    'gct_target',
                    'translations' => [
                        'gctt_lang',
                        'gctt_subject',
                        'gctt_body',
                    ],
                ],
            ),
            ...$this->createRoute(
                name: 'comm-fields',
                path: 'comm-fields/{target:[a-zA-Z0-9-_]+}[/{id:[a-zA-Z0-9-_]+}[/{organizationId:\d+}]]',
                handler: CommFieldsHandler::class,
            ),

            ...$this->createRoute(
                name: 'other-patient-numbers',
                path: 'other-patient-numbers/{patientNr:[a-zA-Z0-9-_]+}/{organizationId:\d+}',
                handler: OtherPatientNumbersHandler::class,
            ),
        ];
    }
}