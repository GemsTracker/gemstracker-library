<?php

namespace Gems\Config;

use Gems\Actions\TrackBuilderAction;
use Gems\Legacy\LegacyController;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\MenuMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Route\ModelSnippetActionRouteHelpers;

class Route
{
    use ModelSnippetActionRouteHelpers;

    public function __invoke()
    {
        $routes = [
            [
                'name' => 'setup.reception.index',
                'path' => '/setup/reception/index',
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LegacyController::class,
                ],
                'allowed_methods' => ['GET'],
                'options' => [
                    'controller' => \Gems_Default_ReceptionAction::class,
                    'action' => 'index',
                ]
            ],

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
                controllerClass: \Gems_Default_SourceAction::class,
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
            ...$this->createBrowseRoutes(baseName: 'track-builder.chartconfig', controllerClass: \Gems_Default_ChartconfigAction::class),
            ...$this->createBrowseRoutes(baseName: 'track-builder.condition', controllerClass: \Gems_Default_ConditionAction::class),
            ...$this->createBrowseRoutes(baseName: 'track-builder.survey-maintenance',
                controllerClass: \Gems_Default_SurveyMaintenanceAction::class,
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
                    'controller' => \Gems_Default_UpdateSurveyAction::class,
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
                    'controller' => \Gems_Default_SurveyCodeBookExportAction::class,
                    'action' => 'export',
                ],
            ],

            ...$this->createBrowseRoutes(baseName: 'track-builder.track-maintenance',
                controllerClass: \Gems_Default_TrackMaintenanceAction::class,
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
                controllerClass: \Gems_Default_TrackFieldsAction::class,
                basePath: 'track-builder/track-maintenance/{trackId:\d+}/track-fields',
                parentParameters: [
                    'trackId',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'track-builder.track-rounds',
                controllerClass: \Gems_Default_TrackFieldsAction::class,
                basePath: 'track-builder/track-maintenance/{trackId:\d+}/track-rounds',
                parentParameters: [
                    'trackId',
                ],
            ),

        ];

        return $routes;
    }
}
