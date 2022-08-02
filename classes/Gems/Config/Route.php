<?php

namespace Gems\Config;

use Gems\Actions\ProjectInformationAction;
use Gems\Actions\TrackBuilderAction;
use Gems\Legacy\LegacyController;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\MenuMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Route\ModelSnippetActionRouteHelpers;

class Route
{
    use ModelSnippetActionRouteHelpers;

    public function __invoke(): array
    {
        return [
            ...$this->getRespondentRoutes(),
            ...$this->getOverviewRoutes(),
            ...$this->getProjectRoutes(),
            ...$this->getSetupRoutes(),
            ...$this->getTrackBuilderRoutes(),
        ];
    }

    public function getOverviewRoutes(): array
    {
        return [
            [
                'name' => 'overview',
                'path' => '/overview',
                'allowed_methods' => ['GET'],
                'middleware' => $this->modelSnippetCustomMiddleware,
            ],
            ...$this->createBrowseRoutes(baseName: 'overview.summary',
                controllerClass: \Gems\Actions\SummaryAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.compliance',
                controllerClass: \Gems\Actions\ComplianceAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.field-report',
                controllerClass: \Gems\Actions\FieldReportAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.field-overview',
                controllerClass: \Gems\Actions\FieldOverviewAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.overview-plan',
                controllerClass: \Gems\Actions\OverviewPlanAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.token-plan',
                controllerClass: \Gems\Actions\TokenPlanAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.respondent-plan',
                controllerClass: \Gems\Actions\RespondentPlanAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'overview.consent-plan',
                controllerClass: \Gems\Actions\ConsentPlanAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
        ];
    }

    public function getProjectRoutes(): array
    {
        return [
            [
                'name' => 'project',
                'path' => '/project',
                'allowed_methods' => ['GET'],
                'middleware' => $this->modelSnippetCustomMiddleware,
            ],
            ...$this->createBrowseRoutes(baseName: 'project.tracks',
                controllerClass: \Gems\Actions\ProjectTracksAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'show',
                ],
                parameterRoutes: [
                    'show',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'project.surveys',
                controllerClass: \Gems\Actions\ProjectSurveysAction::class,
                pages: [
                    'index',
                    'autofilter',
                    'show',
                ],
                parameterRoutes: [
                    'show',
                ],
            ),
        ];
    }

    public function getRespondentRoutes(): array
    {
        return [
            ...$this->createBrowseRoutes(baseName: 'respondent',
                controllerClass: \Gems\Actions\RespondentNewAction::class,
                pages: [
                    ...$this->defaultPages,
                    'change-consent',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'change-consent',
                ],
                parameters: [
                    'id1' => '[a-zA-Z0-9-_]+',
                    'id2' => '\d+',
                ],
            ),
        ];
    }

    public function getSetupRoutes(): array
    {
        return [
            [
                'name' => 'setup',
                'path' => '/setup',
                'allowed_methods' => ['GET'],
                'middleware' => $this->modelSnippetCustomMiddleware,
            ],
            ...$this->createBrowseRoutes(baseName: 'setup.project-information',
                controllerClass: \Gems\Actions\ProjectInformationAction::class,
                pages: [
                    'index',
                    'errors',
                    'php',
                    'php-errors',
                    'project',
                    'session',
                    'changelog-gems',
                    'changelog',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.project-information.upgrade',
                controllerClass: \Gems\Actions\UpgradeAction::class,
                pages: [
                    'index',
                    'compatibility-report',
                    'show',
                ],
                parameterRoutes: [
                    'show',
                ],
                parameters: [
                    'id' => '[a-zA-Z0-9-_]+',
                ],
            ),

            [
                'name' => 'setup.codes',
                'path' => '/setup/codes',
                'allowed_methods' => ['GET'],
                'middleware' => $this->modelSnippetCustomMiddleware,
            ],
            ...$this->createBrowseRoutes(baseName: 'setup.codes.reception',
                controllerClass: \Gems\Actions\ReceptionAction::class,
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.codes.consent',
                controllerClass: \Gems\Actions\ConsentAction::class,
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.codes.mail-code',
                controllerClass: \Gems\Actions\MailCodeAction::class,
            ),

        ];
    }

    public function getTrackBuilderRoutes(): array
    {
        return [
            [
                'name' => 'track-builder',
                'path' => '/track-builder',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    MenuMiddleware::class,
                    TrackBuilderAction::class,
                ],
            ],

            ...$this->createBrowseRoutes(baseName: 'track-builder.source',
                controllerClass: \Gems\Actions\SourceAction::class,
                pages: [
                    ...$this->defaultPages,
                    'synchronize-all',
                    'check-all',
                    'attributes-all',
                    'ping',
                    'synchronize',
                    'attributes',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'ping',
                    'synchronize',
                    'attributes',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'track-builder.chartconfig', controllerClass: \Gems\Actions\ChartconfigAction::class),
            ...$this->createBrowseRoutes(baseName: 'track-builder.condition', controllerClass: \Gems\Actions\ConditionAction::class),
            ...$this->createBrowseRoutes(baseName: 'track-builder.survey-maintenance',
                controllerClass: \Gems\Actions\SurveyMaintenanceAction::class,
                pages: [
                    ...$this->defaultPages,
                    'check-all',
                    'answer-imports',
                    'check',
                    'answer-import',
                    //'export-codebook',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'check',
                    'answer-import',
                    'export-codebook',
                ],
            ),
            [
                'name' => 'track-builder.survey-maintenance.update-survey',
                'path' => '/track-builder/survey-maintenance/update-survey',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LegacyCurrentUserMiddleware::class,
                    MenuMiddleware::class,
                    LegacyController::class,
                ],
                'options' => [
                    'controller' => \Gems\Actions\UpdateSurveyAction::class,
                    'action' => 'run',
                ],
            ],
            [
                'name' => 'track-builder.survey-maintenance.export-codebook',
                'path' => '/track-builder/survey-maintenance/export-codebook/{id:\d+}',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LegacyCurrentUserMiddleware::class,
                    MenuMiddleware::class,
                    LegacyController::class,
                ],
                'options' => [
                    'controller' => \Gems\Actions\SurveyCodeBookExportAction::class,
                    'action' => 'export',
                ],
            ],

            ...$this->createBrowseRoutes(baseName: 'track-builder.track-maintenance',
                controllerClass: \Gems\Actions\TrackMaintenanceAction::class,
                pages: [
                    ...$this->defaultPages,
                    'import',
                    'track-overview',
                    'check-all',
                    'recalc-all-fields',
                    'export',
                    'check-track',
                    'recalc-fields',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'export',
                    'check-track',
                    'recalc-fields',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'track-builder.track-fields',
                controllerClass: \Gems\Actions\TrackFieldsAction::class,
                basePath: 'track-builder/track-maintenance/{trackId:\d+}/track-fields',
                parentParameters: [
                    'trackId',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'track-builder.track-rounds',
                controllerClass: \Gems\Actions\TrackFieldsAction::class,
                basePath: 'track-builder/track-maintenance/{trackId:\d+}/track-rounds',
                parentParameters: [
                    'trackId',
                ],
            ),

        ];
    }
}
