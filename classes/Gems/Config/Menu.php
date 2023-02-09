<?php

namespace Gems\Config;

use Gems\Event\Application\MenuBuildItemsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class Menu
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function getItems(): array
    {
        $items = $this->buildItems();

        $event  = new MenuBuildItemsEvent($items);
        $this->eventDispatcher->dispatch($event);

        return $event->getItems();
    }

    private function buildItems(): array
    {
        return [
            $this->getRespondentMenu(),
            $this->getCalendarMenu(),
            $this->getOverviewMenu(),
            $this->getProjectMenu(),
            $this->getSetupMenu(),
            $this->getTrackBuilderMenu(),
            /*[
                'name' => 'auth.logout',
                'label' => $this->translator->trans('Log out'),
                'type' => 'route-link-item',
            ],*/
        ];
    }

    public function getCalendarMenu(): array
    {
        return [
            'name' => 'calendar.index',
            'label' => $this->translator->trans('Calendar'),
            'type' => 'route-link-item',
            'children' => [
                [
                    'name' => 'calendar.show',
                    'label' => $this->translator->trans('Show'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'calendar.edit',
                            'label' => $this->translator->trans('Edit'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getRespondentMenu(): array
    {
        return  [
            'name' => 'respondent.index',
            'label' => $this->translator->trans('Respondent'),
            'type' => 'route-link-item',
            'children' => [
                [
                    'name' => 'respondent.create',
                    'label' => $this->translator->trans('New'),
                    'type' => 'route-link-item',
                ],
                [
                    'name' => 'respondent.show',
                    'label' => $this->translator->trans('Show'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'respondent.edit',
                            'label' => $this->translator->trans('Edit'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'respondent.change-consent',
                            'label' => $this->translator->trans('Consent'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'respondent.change-organization',
                            'label' => $this->translator->trans('Change organization'),
                            'type' => 'route-link-item',
                        ],


                        [
                            'name' => 'respondent.episodes-of-care.index',
                            'label' => $this->translator->trans('Episodes'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'respondent.episodes-of-care.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'respondent.episodes-of-care.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'respondent.episodes-of-care.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.episodes-of-care.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'respondent.appointments.index',
                            'label' => $this->translator->trans('Appointments'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'respondent.appointments.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'respondent.appointments.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'respondent.appointments.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.appointments.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'respondent.tracks.index',
                            'label' => $this->translator->trans('Tracks'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'respondent.tracks.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'respondent.tracks.show-track',
                                    'label' => $this->translator->trans('Show track'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'respondent.tracks.edit-track',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.delete-track',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.check-track-answers',
                                            'label' => $this->translator->trans('Check answers'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.check-track',
                                            'label' => $this->translator->trans('Check rounds'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.recalc-fields',
                                            'label' => $this->translator->trans('Recalculate fields'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.export-track',
                                            'label' => $this->translator->trans('Export track'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                                [
                                    'name' => 'respondent.tracks.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'respondent.tracks.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.correct',
                                            'label' => $this->translator->trans('Correct answers'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.answer',
                                            'label' => $this->translator->trans('Answers'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.questions',
                                            'label' => $this->translator->trans('Preview'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.check-token',
                                            'label' => $this->translator->trans('Token check'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tracks.check-token-answers',
                                            'label' => $this->translator->trans('(Re)check answers'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'respondent.tokens.index',
                            'label' => $this->translator->trans('Surveys'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'respondent.tokens.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'respondent.tokens.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.tokens.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'respondent.activity-log.index',
                            'label' => $this->translator->trans('Activity log'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'respondent.activity-log.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                        [
                            'name' => 'respondent.relations.index',
                            'label' => $this->translator->trans('Relations'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'respondent.relations.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'respondent.relations.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'respondent.relations.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'respondent.relations.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],

                        [
                            'name' => 'respondent.delete',
                            'label' => $this->translator->trans('Delete'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getOverviewMenu(): array
    {
        return [
            'name' => 'overview',
            'label' => $this->translator->trans('Overview'),
            'type' => 'route-link-item',
            'children' => [
                [
                    'name' => 'overview.summary.index',
                    'label' => $this->translator->trans('Track Summary'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.compliance.index',
                    'label' => $this->translator->trans('Track Compliance'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.field-report.index',
                    'label' => $this->translator->trans('Track Field Utilization'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.field-overview.index',
                    'label' => $this->translator->trans('Track Field Content'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.overview-plan.index',
                    'label' => $this->translator->trans('By period'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.token-plan.index',
                    'label' => $this->translator->trans('By token'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.respondent-plan.index',
                    'label' => $this->translator->trans('By patient'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'overview.consent-plan.index',
                    'label' => $this->translator->trans('Patient status'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'overview.summary.export',
                            'label' => $this->translator->trans('export'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getProjectMenu(): array
    {
        return [
            'name' => 'project',
            'label' => $this->translator->trans('Project'),
            'type' => 'route-link-item',
            'children' => [
                [
                    'name' => 'project.tracks.index',
                    'label' => $this->translator->trans('Tracks'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'project.tracks.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                        ],
                    ]
                ],
                [
                    'name' => 'project.surveys.index',
                    'label' => $this->translator->trans('Surveys'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'project.surveys.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                        ],
                    ]
                ],
            ],
        ];
    }

    public function getSetupMenu()
    {
        return [
            'name' => 'setup',
            'label' => $this->translator->trans('Setup'),
            'type' => 'route-link-item',
            'children' => [
                [
                    'name' => 'setup.project-information.index',
                    'label' => $this->translator->trans('Project setup'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.project-information.errors',
                            'label' => $this->translator->trans('Errors'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'setup.project-information.php',
                            'label' => $this->translator->trans('PHP'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'setup.project-information.php-errors',
                            'label' => $this->translator->trans('PHP Errors'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'setup.project-information.project',
                            'label' => $this->translator->trans('Project settings'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'setup.project-information.session',
                            'label' => $this->translator->trans('Session'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'setup.project-information.upgrade.index',
                            'label' => $this->translator->trans('Upgrade'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.project-information.upgrade.compatibility-report',
                                    'label' => $this->translator->trans('Code compatibility report'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.project-information.changelog-gems',
                                    'label' => $this->translator->trans('Changelog GemsTracker'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.project-information.changelog',
                                    'label' => $this->translator->trans('Project Changelog'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],

                    ],
                ],
                [
                    'name' => 'setup.codes',
                    'label' => $this->translator->trans('Codes'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.codes.reception.index',
                            'label' => $this->translator->trans('Reception codes'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.codes.reception.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.codes.reception.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.codes.reception.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.codes.reception.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.codes.consent.index',
                            'label' => $this->translator->trans('Consents'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.codes.consent.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.codes.consent.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.codes.consent.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.codes.consent.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.codes.mail-code.index',
                            'label' => $this->translator->trans('Mail codes'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.codes.mail-code.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.codes.mail-code.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.codes.mail-code.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.codes.mail-code.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'setup.access',
                    'label' => $this->translator->trans('Access'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.access.roles.index',
                            'label' => $this->translator->trans('Roles'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.access.roles.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.roles.overview',
                                    'label' => $this->translator->trans('Overview'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.roles.privilege',
                                    'label' => $this->translator->trans('Privilege'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.roles.download',
                                    'label' => $this->translator->trans('Download'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.roles.diff',
                                    'label' => $this->translator->trans('Changes'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.roles.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.access.roles.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.roles.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.access.groups.index',
                            'label' => $this->translator->trans('Groups'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.access.groups.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.groups.download',
                                    'label' => $this->translator->trans('Download'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.groups.diff',
                                    'label' => $this->translator->trans('Changes'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.groups.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.access.groups.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.groups.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.access.organizations.index',
                            'label' => $this->translator->trans('Organizations'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.access.organizations.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.organizations.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.access.organizations.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.organizations.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.access.staff.index',
                            'label' => $this->translator->trans('Staff'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.access.staff.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.staff.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.access.staff.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.staff.reset',
                                            'label' => $this->translator->trans('Reset password'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.staff.active-toggle',
                                            'label' => $this->translator->trans('(De)activate'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.staff-log.index',
                                            'label' => $this->translator->trans('Staff log'),
                                            'type' => 'route-link-item',
                                            'children' => [
                                                [
                                                    'name' => 'setup.access.staff-log.show',
                                                    'label' => $this->translator->trans('Show'),
                                                    'type' => 'route-link-item',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.access.system-user.index',
                            'label' => $this->translator->trans('System users'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.access.system-user.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.access.system-user.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.access.system-user.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.access.system-user.active-toggle',
                                            'label' => $this->translator->trans('(De)activate'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'setup.agenda',
                    'label' => $this->translator->trans('Agenda'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.agenda.activity.index',
                            'label' => $this->translator->trans('Activities'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.agenda.activity.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.agenda.activity.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.agenda.activity.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.activity.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.activity.cleanup',
                                            'label' => $this->translator->trans('Clean up'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.agenda.procedure.index',
                            'label' => $this->translator->trans('Procedures'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.agenda.procedure.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.agenda.procedure.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.agenda.procedure.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.procedure.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.procedure.cleanup',
                                            'label' => $this->translator->trans('Clean up'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.agenda.diagnosis.index',
                            'label' => $this->translator->trans('Diagnoses'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.agenda.diagnosis.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.agenda.diagnosis.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.agenda.diagnosis.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.diagnosis.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.agenda.location.index',
                            'label' => $this->translator->trans('Locations'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.agenda.location.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.agenda.location.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.agenda.location.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.location.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.location.cleanup',
                                            'label' => $this->translator->trans('Clean up'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.location.merge',
                                            'label' => $this->translator->trans('Merge'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.agenda.staff.index',
                            'label' => $this->translator->trans('Healthcare staff'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.agenda.staff.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.agenda.staff.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.agenda.staff.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.staff.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.staff.merge',
                                            'label' => $this->translator->trans('Merge'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.agenda.filter.index',
                            'label' => $this->translator->trans('Appointment filters'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.agenda.filter.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.agenda.filter.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.agenda.filter.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.filter.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.agenda.filter.check-filter',
                                            'label' => $this->translator->trans('Check as import'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'setup.communication',
                    'label' => $this->translator->trans('Communication'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.communication.job.index',
                            'label' => $this->translator->trans('Automatic messaging'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.communication.job.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.communication.job.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.communication.job.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.communication.job.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.communication.messenger.index',
                            'label' => $this->translator->trans('Messenger'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.communication.messenger.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.communication.messenger.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.communication.messenger.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.communication.messenger.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.communication.template.index',
                            'label' => $this->translator->trans('Templates'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.communication.template.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.communication.template.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.communication.template.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.communication.template.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.communication.server.index',
                            'label' => $this->translator->trans('Mail servers'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.communication.server.create',
                                    'label' => $this->translator->trans('New'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'setup.communication.server.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'setup.communication.server.edit',
                                            'label' => $this->translator->trans('Edit'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'setup.communication.server.delete',
                                            'label' => $this->translator->trans('Delete'),
                                            'type' => 'route-link-item',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'setup.communication.log.index',
                            'label' => $this->translator->trans('Communication log'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.communication.log.show',
                                    'label' => $this->translator->trans('Show'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'setup.log.maintenance.index',
                    'label' => $this->translator->trans('Log Setup'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.log.maintenance.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'setup.log.maintenance.edit',
                                    'label' => $this->translator->trans('Edit'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'setup.log.activity.index',
                    'label' => $this->translator->trans('Activity log'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'setup.log.activity.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getTrackBuilderMenu(): array
    {
        return [
            'name' => 'track-builder',
            'label' => $this->translator->trans('Track Builder'),
            'type' => 'route-link-item',
            'children' => [
                [
                    'name' => 'track-builder.source.index',
                    'label' => $this->translator->trans('Source'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'track-builder.source.create',
                            'label' => $this->translator->trans('New'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.source.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'track-builder.source.edit',
                                    'label' => $this->translator->trans('Edit'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.delete',
                                    'label' => $this->translator->trans('Delete'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.ping',
                                    'label' => $this->translator->trans('Check status'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.synchronize',
                                    'label' => $this->translator->trans('Synchronize surveys'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.check',
                                    'label' => $this->translator->trans('Check answers'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.attributes',
                                    'label' => $this->translator->trans('Check attributes'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                        [
                            'name' => 'track-builder.source.synchronize-all',
                            'label' => $this->translator->trans('Synchronize all surveys'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.source.check-all',
                            'label' => $this->translator->trans('Check all is answered'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.source.attributes-all',
                            'label' => $this->translator->trans('Check all attributes'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'track-builder.chartconfig.index',
                    'label' => $this->translator->trans('Chart config'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'track-builder.chartconfig.create',
                            'label' => $this->translator->trans('Create'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.chartconfig.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'track-builder.chartconfig.edit',
                                    'label' => $this->translator->trans('Edit'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.chartconfig.delete',
                                    'label' => $this->translator->trans('Delete'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'track-builder.condition.index',
                    'label' => $this->translator->trans('Conditions'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'track-builder.condition.create',
                            'label' => $this->translator->trans('Create'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.condition.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'track-builder.condition.edit',
                                    'label' => $this->translator->trans('Edit'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.condition.delete',
                                    'label' => $this->translator->trans('Delete'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'track-builder.survey-maintenance.index',
                    'label' => $this->translator->trans('Surveys'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'track-builder.survey-maintenance.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'track-builder.survey-maintenance.edit',
                                    'label' => $this->translator->trans('Edit'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.survey-maintenance.check',
                                    'label' => $this->translator->trans('Check is answered'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.survey-maintenance.answer-import',
                                    'label' => $this->translator->trans('Import answers'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.survey-maintenance.export-codebook.export',
                                    'label' => $this->translator->trans('Export codebook'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.survey-maintenance.update-survey.run',
                                    'label' => $this->translator->trans('Update to new survey'),
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                        [
                            'name' => 'track-builder.survey-maintenance.check-all',
                            'label' => $this->translator->trans('Check all is answered'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.survey-maintenance.answer-imports',
                            'label' => $this->translator->trans('Import answers'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.survey-maintenance.update-survey.run',
                            'label' => $this->translator->trans('Update to new survey'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
                [
                    'name' => 'track-builder.track-maintenance.index',
                    'label' => $this->translator->trans('Tracks'),
                    'type' => 'route-link-item',
                    'children' => [
                        [
                            'name' => 'track-builder.track-maintenance.create',
                            'label' => $this->translator->trans('Create'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.track-maintenance.show',
                            'label' => $this->translator->trans('Show'),
                            'type' => 'route-link-item',
                            'children' => [
                                [
                                    'name' => 'track-builder.track-maintenance.edit',
                                    'label' => $this->translator->trans('Edit'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.track-maintenance.delete',
                                    'label' => $this->translator->trans('Delete'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.track-maintenance.export',
                                    'label' => $this->translator->trans('Export'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.track-maintenance.check-track',
                                    'label' => $this->translator->trans('Check rounds'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.track-maintenance.recalc-fields',
                                    'label' => $this->translator->trans('Recalculate fields'),
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.track-maintenance.track-fields.index',
                                    'label' => $this->translator->trans('Fields'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'track-builder.track-maintenance.track-fields.create',
                                            'label' => $this->translator->trans('Create'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'track-builder.track-maintenance.track-fields.show',
                                            'label' => $this->translator->trans('Show'),
                                            'type' => 'route-link-item',
                                            'children' => [
                                                [
                                                    'name' => 'track-builder.track-maintenance.track-fields.edit',
                                                    'label' => $this->translator->trans('Edit'),
                                                    'type' => 'route-link-item',
                                                ],
                                                [
                                                    'name' => 'track-builder.track-maintenance.track-fields.delete',
                                                    'label' => $this->translator->trans('Delete'),
                                                    'type' => 'route-link-item',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'name' => 'track-builder.track-maintenance.track-rounds.index',
                                    'label' => $this->translator->trans('Rounds'),
                                    'type' => 'route-link-item',
                                    'children' => [
                                        [
                                            'name' => 'track-builder.track-maintenance.track-rounds.create',
                                            'label' => $this->translator->trans('Create'),
                                            'type' => 'route-link-item',
                                        ],
                                        [
                                            'name' => 'track-builder.track-maintenance.track-rounds.show',
                                            'label' => $this->translator->trans('Show'),
                                            'type' => 'route-link-item',
                                            'children' => [
                                                [
                                                    'name' => 'track-builder.track-maintenance.track-rounds.edit',
                                                    'label' => $this->translator->trans('Edit'),
                                                    'type' => 'route-link-item',
                                                ],
                                                [
                                                    'name' => 'track-builder.track-maintenance.track-rounds.delete',
                                                    'label' => $this->translator->trans('Delete'),
                                                    'type' => 'route-link-item',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'track-builder.track-maintenance.track-overview.index',
                            'label' => $this->translator->trans('Track per org'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.track-maintenance.check-all',
                            'label' => $this->translator->trans('Check all rounds'),
                            'type' => 'route-link-item',
                        ],
                        [
                            'name' => 'track-builder.track-maintenance.recalc-all-fields',
                            'label' => $this->translator->trans('Recalculate all fields'),
                            'type' => 'route-link-item',
                        ],
                    ],
                ],
            ],
        ];
    }
}
