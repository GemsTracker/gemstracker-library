<?php

namespace Gems\Config;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationWithoutTfaMiddleware;
use Gems\AuthNew\NotAuthenticatedMiddleware;
use Gems\Handlers\Auth\AuthIdleCheckHandler;
use Gems\Handlers\Auth\ChangePasswordHandler;
use Gems\Handlers\Auth\EmbedLoginHandler;
use Gems\Handlers\Auth\LoginHandler;
use Gems\Handlers\Auth\LogoutHandler;
use Gems\Handlers\Auth\RequestPasswordResetHandler;
use Gems\Handlers\Auth\ResetPasswordChangeHandler;
use Gems\Handlers\Auth\TfaLoginHandler;
use Gems\Handlers\ChangeGroupHandler;
use Gems\Handlers\ChangeLanguageHandler;
use Gems\Handlers\ChangeOrganizationHandler;
use Gems\Handlers\EmptyHandler;
use Gems\Handlers\InfoHandler;
use Gems\Handlers\Respondent\CalendarHandler;
use Gems\Handlers\Respondent\RespondentShowHandler;
use Gems\Middleware\AclMiddleware;
use Gems\Middleware\AuditLogMiddleware;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Middleware\HandlerCsrfMiddleware;
use Gems\Middleware\LegacyCurrentUserMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\MenuMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Gems\Model;
use Gems\Route\ModelSnippetActionRouteHelpers;
use Gems\Util\RouteGroupTrait;
use Mezzio\Csrf\CsrfMiddleware;
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
                    ClientIpMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    CsrfMiddleware::class,
                    LocaleMiddleware::class,
                    AuthenticationMiddleware::class,
                    AclMiddleware::class,
                    CurrentOrganizationMiddleware::class,
                    AuditLogMiddleware::class,
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
                ...$this->getApiRoutes(),
            ]),
        ];
    }

    public function getApiRoutes(): array
    {
        $apiRoutes = new ApiRoutes();
        return $apiRoutes();
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
                    CsrfMiddleware::class,
                    HandlerCsrfMiddleware::class,
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
                    CsrfMiddleware::class,
                    HandlerCsrfMiddleware::class,
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
                'name' => 'info.show',
                'path' => '/info',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    LocaleMiddleware::class,
                    MenuMiddleware::class,
                    InfoHandler::class,
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
                    CsrfMiddleware::class,
                    HandlerCsrfMiddleware::class,
                    NotAuthenticatedMiddleware::class,
                    RequestPasswordResetHandler::class,
                    ],
            ],
            [
                'name' => 'auth.password-reset.change',
                'path' => '/index/resetpassword/key/{key:[a-zA-Z0-9]+}',
                'allowed_methods' => ['GET', 'POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    SessionMiddleware::class,
                    FlashMessageMiddleware::class,
                    CsrfMiddleware::class,
                    HandlerCsrfMiddleware::class,
                    NotAuthenticatedMiddleware::class,
                    ResetPasswordChangeHandler::class,
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
            [
                'name' => 'auth.change-password',
                'path' => '/change-password',
                'allowed_methods' => ['GET', 'POST'],
                'middleware' => [
                    SecurityHeadersMiddleware::class,
                    LocaleMiddleware::class,
                    HandlerCsrfMiddleware::class,
                    ChangePasswordHandler::class,
                ],
            ],
            [
                'name' => 'organization.switch-ui',
                'path' => '/organization/switch-ui',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    ChangeOrganizationHandler::class,
                ],
            ],
            [
                'name' => 'group.switch-ui',
                'path' => '/group/switch-ui',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    ChangeGroupHandler::class,
                ],
            ],
        ];
    }

    public function getAskRoutes(): array
    {
        return [
            ...$this->createSnippetRoutes(baseName: 'ask',
                controllerClass: \Gems\Handlers\AskHandler::class,
                pages: [
                    'index',
                    'forward',
                    'take',
                    'to-survey',
                    'return',
                ],
                parameterRoutes: [
                    'forward',
                    'take',
                    'to-survey',
                    'return',
                ],
                parameters: [
                    'id' => '[a-zA-Z0-9]{4}[_-][a-zA-Z0-9]{4}',
                ],
            ),
        ];
    }

    public function getCalendarRoutes(): array
    {
        return [
            ...$this->createSnippetRoutes(
                baseName: 'calendar',
                controllerClass: CalendarHandler::class,
                // pages: ['index', 'autofilter'],
                parameters: [Model::APPOINTMENT_ID =>  '\d+',],
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
            ...$this->createSnippetRoutes(baseName: 'overview.summary',
                controllerClass:                   \Gems\Handlers\Overview\SummaryHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.compliance',
                controllerClass:                   \Gems\Handlers\Overview\ComplianceHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.field-report',
                controllerClass: \Gems\Handlers\Overview\FieldReportHandler::class,
                pages: [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.field-overview',
                controllerClass:                   \Gems\Handlers\Overview\FieldOverviewHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.overview-plan',
                controllerClass:                   \Gems\Handlers\Overview\OverviewPlanHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.token-plan',
                controllerClass:                   \Gems\Handlers\Overview\TokenPlanHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.respondent-plan',
                controllerClass:                   \Gems\Handlers\Overview\RespondentPlanHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'export',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'overview.consent-plan',
                controllerClass:                   \Gems\Handlers\Overview\ConsentPlanHandler::class,
                pages:                             [
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
            ...$this->createSnippetRoutes(baseName: 'project.tracks',
                controllerClass:                   \Gems\Handlers\Project\ProjectTracksHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'show',
                ],
                parameterRoutes:                   [
                    'show',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'project.surveys',
                controllerClass:                   \Gems\Handlers\Project\ProjectSurveysHandler::class,
                pages:                             [
                    'index',
                    'autofilter',
                    'show',
                ],
                parameterRoutes:                   [
                    'show',
                ],
            ),
        ];
    }

    public function getRespondentRoutes(): array
    {
        return [
            ...$this->createSnippetRoutes(baseName: 'respondent',
                controllerClass: \Gems\Handlers\Respondent\RespondentHandler::class,
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
            [
                'name' => 'respondent-show',
                'path' => '/respondent-show/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    LegacyCurrentUserMiddleware::class,
                    RespondentShowHandler::class,
                ],
            ],
            ...$this->createSnippetRoutes(baseName: 'respondent.episodes-of-care',
                controllerClass:                   \Gems\Handlers\Respondent\CareEpisodeHandler::class,
                basePath:                          '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/episodes-of-care',
                parentParameters:                  [
                    'id1',
                    'id2',
                ],
                parameters:                        [
                    \Gems\Model::EPISODE_ID => '\d+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'respondent.appointments',
                controllerClass: \Gems\Handlers\Respondent\AppointmentHandler::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/appointments',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters:                        [
                    \Gems\Model::APPOINTMENT_ID => '\d+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Handlers\Respondent\TrackHandler::class,
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
            ...$this->createSnippetRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Handlers\Respondent\TrackHandler::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tracks',
                pages: [
                    'show-track',
                    'edit-track',
                    'delete-track',
                    'check-track-answers',
                    'check-track',
                    'recalc-fields',
                    'export-track',
                ],
                parameterRoutes: [
                    'show-track',
                    'edit-track',
                    'delete-track',
                    'check-track-answers',
                    'check-track',
                    'recalc-fields',
                    'export-track',
                ],
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::RESPONDENT_TRACK => '\d+',
                ],
                postRoutes: [
                    'edit-track',
                ]
            ),
            ...$this->createSnippetRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Handlers\Respondent\TrackHandler::class,
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
            ...$this->createSnippetRoutes(baseName: 'respondent.tracks',
                controllerClass: \Gems\Handlers\Respondent\TrackHandler::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tracks',
                pages: [
                    'insert',
                    'view-survey',
                ],
                parameterRoutes: [
                    'insert',
                    'view-survey',
                ],
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::SURVEY_ID => '\d+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'respondent.tokens',
                controllerClass: \Gems\Handlers\Respondent\TokenHandler::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/tokens',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::SURVEY_ID => '\d+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'respondent.activity-log',
                controllerClass: \Gems\Handlers\Respondent\RespondentLogHandler::class,
                basePath: '/respondent/{id1:[a-zA-Z0-9-_]+}/{id2:\d+}/activity-log',
                parentParameters: [
                    'id1',
                    'id2',
                ],
                parameters: [
                    \Gems\Model::LOG_ITEM_ID => '\d+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'respondent.relations',
                controllerClass: \Gems\Handlers\Respondent\RespondentRelationHandler::class,
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
            ...$this->createSnippetRoutes(baseName: 'setup.project-information',
                controllerClass: \Gems\Handlers\Setup\ProjectInformationHandler::class,
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


            ...$this->createSnippetRoutes(baseName: 'setup.codes.reception',
                controllerClass:                   \Gems\Handlers\Setup\ReceptionCodeHandler::class,
                parameters:                        [
                    'id' => '[a-zA-Z0-9-_]+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.codes.consent',
                controllerClass: \Gems\Handlers\Setup\ConsentHandler::class,
                parameters: [
                    'gco_description' => '[a-zA-Z0-9-_]+',
                ],
            ),

            ...$this->createSnippetRoutes(baseName: 'setup.codes.mail-code',
                controllerClass: \Gems\Handlers\Setup\MailCodeHandler::class,
            ),

            [
                'name' => 'setup.communication',
                'path' => '/setup/communication',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    EmptyHandler::class,
                ],
            ],
            ...$this->createSnippetRoutes(baseName: 'setup.communication.job',
                controllerClass: \Gems\Handlers\Setup\CommJobHandler::class,
                parameters: [
                    'id' => '[a-zA-Z0-9-_]+',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.communication.messenger',
                controllerClass: \Gems\Handlers\Setup\CommMessengersHandler::class,
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.communication.template',
                controllerClass: \Gems\Handlers\Setup\CommTemplateHandler::class,
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.communication.server',
                controllerClass: \Gems\Handlers\Setup\MailServerHandler::class,
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.communication.log',
                controllerClass: \Gems\Handlers\Setup\CommLogHandler::class,
            ),

            [
                'name' => 'setup.access',
                'path' => '/setup/access',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    EmptyHandler::class,
                ],
            ],
            ...$this->createSnippetRoutes(baseName: 'setup.access.roles',
                controllerClass: \Gems\Handlers\Setup\RoleHandler::class,
                pages: [
                    'index',
                    'autofilter',
                    'create',
                    'edit',
                    'delete',
                    'overview',
                    'privilege',
                    'download',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.roles',
                controllerClass: \Gems\Handlers\Setup\RoleDiffHandler::class,
                pages: [
                    'diff',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.roles',
                controllerClass: \Gems\Handlers\Setup\RoleHandler::class,
                basePath: '/setup/access/roles/show',
                pages: [
                    'show',
                ],
                parameters: [
                    'id' => '\d+|[a-z\d]+', // static config storage uses role names in urls. int check is present in RoleHandler
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.groups',
                controllerClass: \Gems\Handlers\Setup\GroupHandler::class,
                pages: [
                    ...$this->defaultPages,
                    'download',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.groups',
                controllerClass: \Gems\Handlers\Setup\GroupDiffHandler::class,
                pages: [
                    'diff',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.organizations',
                controllerClass: \Gems\Handlers\Setup\OrganizationHandler::class,
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.staff',
                controllerClass: \Gems\Handlers\Setup\StaffHandler::class,
                pages: [
                    'index',
                    'autofilter',
                    'create',
                    'show',
                    'edit',
                    'reset',
                    'deactivate',

                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'reset',
                    'deactivate',
                    'staff-log',
                ]
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.access.staff-log',
                controllerClass: \Gems\Handlers\Setup\StaffLogHandler::class,
                pages: [
                    'index',
                    'autofilter',
                    'show',
                ],
                parameterRoutes: [
                    'show',
                ]
            ),

            [
                'name' => 'setup.agenda',
                'path' => '/setup/agenda',
                'allowed_methods' => ['GET'],
                'middleware' => [
                    EmptyHandler::class,
                ],
            ],
            ...$this->createSnippetRoutes(baseName: 'setup.agenda.activity',
                controllerClass:                   \Gems\Handlers\Setup\AgendaActivityHandler::class,
                pages:                             [
                    ...$this->defaultPages,
                    'cleanup',
                ],
                parameterRoutes:                   [
                    ...$this->defaultParameterRoutes,
                    'cleanup',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.agenda.procedure',
                controllerClass:                   \Gems\Handlers\Setup\AgendaProcedureHandler::class,
                pages:                             [
                    ...$this->defaultPages,
                    'cleanup',
                ],
                parameterRoutes:                   [
                    ...$this->defaultParameterRoutes,
                    'cleanup',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.agenda.diagnosis',
                controllerClass:                   \Gems\Handlers\Setup\AgendaDiagnosisHandler::class,
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.agenda.location',
                controllerClass:                   \Gems\Handlers\Setup\LocationHandler::class,
                pages:                             [
                    ...$this->defaultPages,
                    'cleanup',
                    'merge',
                ],
                parameterRoutes:                   [
                    ...$this->defaultParameterRoutes,
                    'cleanup',
                    'merge',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.agenda.staff',
                controllerClass:                   \Gems\Handlers\Setup\AgendaStaffHandler::class,
                pages:                             [
                    ...$this->defaultPages,
                    'merge',
                ],
                parameterRoutes:                   [
                    ...$this->defaultParameterRoutes,
                    'merge',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'setup.agenda.filter',
                controllerClass: \Gems\Handlers\Setup\AgendaFilterHandler::class,
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

            ...$this->createSnippetRoutes(baseName: 'track-builder.source',
                controllerClass: \Gems\Handlers\TrackBuilder\SourceHandler::class,
                pages: [
                    ...$this->defaultPages,
                    'synchronize-all',
                    'check-all',
                    'attributes-all',
                    'ping',
                    'synchronize',
                    'check',
                    'attributes',
                ],
                parameterRoutes: [
                    ...$this->defaultParameterRoutes,
                    'ping',
                    'synchronize',
                    'attributes',
                    'check',
                ],
            ),
            //...$this->createBrowseRoutes(baseName: 'track-builder.chartconfig', controllerClass: \Gems\Actions\ChartconfigAction::class),

            ...$this->createSnippetRoutes(baseName: 'track-builder.chartconfig', controllerClass: \Gems\Handlers\TrackBuilder\ChartconfigHandler::class),

            ...$this->createSnippetRoutes(baseName: 'track-builder.condition', controllerClass: \Gems\Handlers\TrackBuilder\ConditionHandler::class),
            ...$this->createSnippetRoutes(baseName: 'track-builder.survey-maintenance',
                controllerClass: \Gems\Handlers\TrackBuilder\SurveyMaintenanceHandler::class,
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
            ...$this->createSnippetRoutes(baseName: 'track-builder.survey-maintenance.update-survey',
                controllerClass: \Gems\Handlers\TrackBuilder\UpdateSurveyHandler::class,
                pages: [
                    'run',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'track-builder.survey-maintenance.export-codebook',
                controllerClass: \Gems\Handlers\TrackBuilder\SurveyCodeBookExportHandler::class,
                pages: [
                    'export',
                ],
                parameterRoutes: [
                    'export',
                ],
            ),

            ...$this->createSnippetRoutes(baseName: 'track-builder.track-maintenance',
                controllerClass: \Gems\Handlers\TrackBuilder\TrackMaintenanceHandler::class,
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
            ...$this->createSnippetRoutes(baseName: 'track-builder.track-maintenance.track-fields',
                controllerClass: \Gems\Handlers\TrackBuilder\TrackFieldsHandler::class,
                basePath: '/track-builder/track-maintenance/{trackId:\d+}/track-fields',
                parameters: [
                    \Gems\Model::FIELD_ID => '\d+',
                    'sub' => '[a-zA-Z]',
                ],
                parentParameters: [
                    'trackId',
                ],
            ),
            ...$this->createSnippetRoutes(baseName: 'track-builder.track-maintenance.track-rounds',
                controllerClass:\Gems\Handlers\TrackBuilder\TrackRoundsHandler::class,
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
