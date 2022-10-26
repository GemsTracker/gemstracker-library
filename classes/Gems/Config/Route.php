<?php

namespace Gems\Config;

use Gems\Actions\CalendarAction;
use Gems\Actions\ProjectInformationAction;
use Gems\Actions\TrackBuilderAction;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationWithoutTfaMiddleware;
use Gems\Dev\Middleware\TestCurrentUserMiddleware;
use Gems\Handlers\Auth\AuthIdleCheckHandler;
use Gems\Handlers\Auth\EmbedLoginHandler;
use Gems\Handlers\Auth\LoginHandler;
use Gems\Handlers\Auth\LogoutHandler;
use Gems\Handlers\Auth\RequestPasswordResetHandler;
use Gems\Handlers\Auth\TfaLoginHandler;
use Gems\AuthNew\NotAuthenticatedMiddleware;
use Gems\Handlers\ChangeLanguageHandler;
use Gems\Handlers\EmptyHandler;
use Gems\Legacy\LegacyController;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\MenuMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Model;
use Gems\Route\ModelSnippetActionRouteHelpers;
use Gems\Util\RouteGroupTrait;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Session\SessionMiddleware;

class Route
{
    use ModelSnippetActionRouteHelpers;
    use RouteGroupTrait;

    public function __invoke(): array
    {
        return [
            ...$this->getLoggedOutRoutes(),

            ...$this->routeGroup([
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    CsrfMiddleware::class,
                    LocaleMiddleware::class,
                    AuthenticationMiddleware::class,
                    MenuMiddleware::class,
                ],
            ], [
                ...$this->getGeneralRoutes(),
                ...$this->getAskRoutes(),
                ...$this->getCalendarRoutes(),
                ...$this->getRespondentRoutes(),
                ...$this->getOverviewRoutes(),
                ...$this->getProjectRoutes(),
                ...$this->getSetupRoutes(),
                ...$this->getTrackBuilderRoutes(),
            ]),
        ];
    }

    public function getLoggedOutRoutes(): array
    {
        return [
            [
                'name' => 'auth.login',
                'path' => '/login',
                'allowed_methods' => ['GET', 'POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    NotAuthenticatedMiddleware::class,
                    LoginHandler::class,
                ],
            ],
            [
                'name' => 'tfa.login',
                'path' => '/tfa',
                'allowed_methods' => ['GET', 'POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    AuthenticationWithoutTfaMiddleware::class,
                    TfaLoginHandler::class,
                ],
            ],
            [
                'name' => 'embed.login',
                'path' => '/embed/login',
                'allowed_methods' => ['GET', 'POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    EmbedLoginHandler::class,
                ],
            ],
            [
                'name' => 'auth.idle.poll',
                'path' => '/auth/idle-poll',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    AuthIdleCheckHandler::class,
                ],
            ],
            [
                'name' => 'auth.idle.alive',
                'path' => '/auth/idle-alive',
                'allowed_methods' => ['POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    AuthIdleCheckHandler::class,
                ],
            ],
            [
                'name' => 'language.change',
                'path' => '/change-language/{language:[a-zA-Z0-9]+}',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    ChangeLanguageHandler::class,
                ],
            ],
            [
                'name' => 'auth.password-reset.request',
                'path' => '/password-reset',
                'allowed_methods' => ['GET', 'POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    NotAuthenticatedMiddleware::class,
                    RequestPasswordResetHandler::class,
                ],
            ],
        ];
    }

    public function getGeneralRoutes(): array
    {
        return [
            [
                'name' => 'auth.logout',
                'path' => '/logout',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    LogoutHandler::class,
                ],
            ],
        ];
    }

    public function getAskRoutes(): array
    {
        return [
            ...$this->createBrowseRoutes(baseName: 'ask',
                controllerClass: \Gems\Actions\AskAction::class,
                pages: [
                    'index',
                    'forward',
                    'take',
                    'to-survey',
                ],
                parameterRoutes: [
                    'forward',
                    'take',
                    'to-survey',
                ],
                parameters: [
                    'id' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
                ],
            ),
        ];
    }

    public function getCalendarRoutes(): array
    {
        return [
            ...$this->createBrowseRoutes(baseName: 'calendar',
                controllerClass: CalendarAction::class,
                parameters: [
                    Model::APPOINTMENT_ID =>  '\d+',
                ],
            ),
        ];
    }

    public function getOverviewRoutes(): array
    {
        return [
            [
                'name' => 'overview',
                'path' => '/overview',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    EmptyHandler::class,
                ]
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
                'middleware' => [
                    EmptyHandler::class
                ],
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
                    'change-organization',
                    'export-archive',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'change-consent',
                    'change-organization',
                    'export-archive',
                ],
                parameters: [
                    'id1' => '[a-zA-Z0-9-_]+',
                    'id2' => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.episodes-of-care',
                controllerClass: \Gems\Actions\CareEpisodeAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/episodes-of-care',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::EPISODE_ID => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.appointments',
                controllerClass: \Gems\Actions\AppointmentAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/appointments',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::APPOINTMENT_ID => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Actions\TrackAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tracks',
                pages: [
                    'index',
                    'create',
                    'view',
                ],
                parameterRoutes: [
                    'create',
                    'view',
                ],
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::TRACK_ID => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Actions\TrackAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tracks',
                pages: [
                    'show-track',
                    'edit-track',
                ],
                parameterRoutes: [
                    'show-track',
                    'edit-track',
                ],
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::RESPONDENT_TRACK => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Actions\TrackAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tracks',
                pages: [
                    'answer',
                    'delete',
                    'edit',
                    'show',
                    'answer-export',
                    'questions',
                    'email',
                    'correct',
                    'check-token',
                    'check-token-answers',
                ],
                parameterRoutes: [
                    'answer',
                    'delete',
                    'edit',
                    'show',
                    'answer-export',
                    'questions',
                    'email',
                    'correct',
                    'check-token',
                    'check-token-answers',
                ],
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    'id' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.tokens',
                controllerClass: \Gems\Actions\TokenAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tokens',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::SURVEY_ID => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.activity-log',
                controllerClass: \Gems\Actions\RespondentLogAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/activity-log',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::LOG_ITEM_ID => '\d+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'respondent.relations',
                controllerClass: \Gems\Actions\RespondentRelationAction::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/relations',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    'rid' => '\d+',
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
                'middleware' => [
                    EmptyHandler::class,
                ],
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
                    'cacheclean',
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
                'middleware' => [
                    EmptyHandler::class,
                ],
            ],
            ...$this->createBrowseRoutes(baseName: 'setup.codes.reception',
                controllerClass: \Gems\Actions\ReceptionAction::class,
                parameters: [
                    'id' => '[a-zA-Z0-9-_]+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.codes.consent',
                controllerClass: \Gems\Actions\ConsentAction::class,
                parameters: [
                    'id' => '[a-zA-Z0-9-_]+',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.codes.mail-code',
                controllerClass: \Gems\Actions\MailCodeAction::class,
            ),

            [
                'name' => 'setup.access',
                'path' => '/setup/access',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    EmptyHandler::class,
                ],
            ],
            ...$this->createBrowseRoutes(baseName: 'setup.access.organizations',
                controllerClass: \Gems\Actions\OrganizationAction::class,
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.access.staff',
                controllerClass: \Gems\Actions\StaffAction::class,
            ),

            [
                'name' => 'setup.agenda',
                'path' => '/setup/agenda',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    EmptyHandler::class,
                ],
            ],
            ...$this->createBrowseRoutes(baseName: 'setup.agenda.activity',
                controllerClass: \Gems\Actions\AgendaActivityAction::class,
                pages: [
                    ...$this->defaultPages,
                    'cleanup',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'cleanup',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.agenda.procedure',
                controllerClass: \Gems\Actions\AgendaProcedureAction::class,
                pages: [
                    ...$this->defaultPages,
                    'cleanup',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'cleanup',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.agenda.diagnosis',
                controllerClass: \Gems\Actions\AgendaDiagnosisAction::class,
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.agenda.location',
                controllerClass: \Gems\Actions\LocationAction::class,
                pages: [
                    ...$this->defaultPages,
                    'cleanup',
                    'merge',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'cleanup',
                    'merge',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.agenda.staff',
                controllerClass: \Gems\Actions\AgendaStaffAction::class,
                pages: [
                    ...$this->defaultPages,
                    'merge',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'merge',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'setup.agenda.filter',
                controllerClass: \Gems\Actions\AgendaFilterAction::class,
                pages: [
                    ...$this->defaultPages,
                    'check-filter',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'check-filter',
                ],
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
                    EmptyHandler::class,
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
                    'index',
                    'autofilter',
                    'show',
                    'edit',
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
                'params' => [
                    'id',
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
                parameters: [
                    'trackId' => '\d+',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'export',
                    'check-track',
                    'recalc-fields',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'track-builder.track-maintenance.track-fields',
                controllerClass: \Gems\Actions\TrackFieldsAction::class,
                basePath: '/track-builder/track-maintenance/{trackId:\d+}/track-fields',
                parentParameters: [
                    'trackId',
                ],
            ),
            ...$this->createBrowseRoutes(baseName: 'track-builder.track-maintenance.track-rounds',
                controllerClass: \Gems\Actions\TrackRoundsAction::class,
                basePath: '/track-builder/track-maintenance/{trackId:\d+}/track-rounds',
                parameters: [
                    \Gems\Model::ROUND_ID => '\d+',
                ],
                parentParameters: [
                    'trackId',
                ],
            ),

        ];
    }
}
