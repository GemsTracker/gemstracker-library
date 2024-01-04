<?php

namespace Gems\Config;

use Gems\Api\Handlers\PingHandler;
use Gems\Api\RestModelConfigProviderAbstract;
use Gems\Handlers\Api\CommFieldsHandler;
use Gems\Handlers\Api\Respondent\OtherPatientNumbersHandler;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Model\CommTemplateModel;
use Gems\Model\EmailTokenModel;
use Gems\Model\InsertableQuestionnaireModel;
use Gems\Model\SimpleTrackModel;
use Gems\Model\Type\CommTemplateSingleLanguageFlatModel;

class ApiRoutes extends RestModelConfigProviderAbstract
{

    public function __invoke()
    {
        return [
            ...$this->routeGroup(
                [
                    'path' => $this->pathPrefix,
                    'middleware' => $this->getMiddleware(),
                ],
                $this->getRoutes()
            ),
            ...$this->routeGroup(
                [
                    'path' => $this->pathPrefix,
                    'middleware' => [
                        SecurityHeadersMiddleware::class,
                        LocaleMiddleware::class,
                    ],
                ],
                [
                    ...$this->createRoute(
                        name: 'status',
                        path: '/status',
                        handler: PingHandler::class,
                        allowedMethods: ['GET'],
                    ),
                ]
            ),

        ];
    }

    public function getRoutes(): array
    {
        return [
            ...$this->createRoute(
                name: 'ping',
                path: '/ping',
                handler: PingHandler::class,
                allowedMethods: ['GET'],
            ),
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
                applySettings: ['applyDetailSettings'],
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
            ...$this->createModelRoute(
                endpoint: 'single-language-comm-template',
                applySettings: ['applyDetailSettings'],
                model: CommTemplateSingleLanguageFlatModel::class,
                methods: ['GET'],
                allowedFields: [
                    'id',
                    'name',
                    'code',
                    'mailTarget',
                    'subject',
                    'body',
                ],
            ),

            ...$this->createModelRoute(
                endpoint: 'respondent/email-token',
                model: EmailTokenModel::class,
                methods: ['GET', 'PATCH'],
                idRegex: '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
                allowedFields: [
                    'id',
                    'to',
                    'trackName',
                    'roundName',
                    'surveyName',
                    'lastContact',
                    'preferredLanguage',
                    'template',
                    'subject',
                    'body',
                ],
                allowedSaveFields: [
                    'gto_id_token',
                    'to',
                    'template',
                    'subject',
                    'body',
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