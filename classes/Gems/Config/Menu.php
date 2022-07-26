<?php

namespace Gems\Config;

class Menu
{
    public function __invoke(): array
    {
        return [
            [
                'name' => 'respondent.index',
                'label' => 'Respondent',
                'type' => 'route-link-item',
                'children' => [
                    [
                        'name' => 'respondent.create',
                        'label' => 'New',
                        'type' => 'route-link-item',
                    ],
                    [
                        'name' => 'respondent.show',
                        'label' => 'Show',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'respondent.edit',
                                'label' => 'Edit',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'respondent.delete',
                                'label' => 'Delete',
                                'type' => 'route-link-item',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'track-builder',
                'label' => 'Track Builder',
                'type' => 'route-link-item',
                'children' => [
                    [
                        'name' => 'track-builder.source.index',
                        'label' => 'Source',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'track-builder.source.create',
                                'label' => 'New',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.source.show',
                                'label' => 'Show',
                                'type' => 'route-link-item',
                                'children' => [
                                    [
                                        'name' => 'track-builder.source.edit',
                                        'label' => 'Edit',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.source.delete',
                                        'label' => 'Delete',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.source.ping',
                                        'label' => 'Check status',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.source.synchronize',
                                        'label' => 'Synchronize surveys',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.source.attributes',
                                        'label' => 'Check attributes',
                                        'type' => 'route-link-item',
                                    ],
                                ],
                                [
                                    'name' => 'track-builder.source.synchronize-all',
                                    'label' => 'Synchronize all surveys',
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.check-all',
                                    'label' => 'Check all is answered',
                                    'type' => 'route-link-item',
                                ],
                                [
                                    'name' => 'track-builder.source.attributes-all',
                                    'label' => 'Check all attributes',
                                    'type' => 'route-link-item',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'track-builder.chartconfig.index',
                        'label' => 'Chart config',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'track-builder.chartconfig.create',
                                'label' => 'Create',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.chartconfig.show',
                                'label' => 'Show',
                                'type' => 'route-link-item',
                                'children' => [
                                    [
                                        'name' => 'track-builder.chartconfig.edit',
                                        'label' => 'Edit',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.chartconfig.delete',
                                        'label' => 'Delete',
                                        'type' => 'route-link-item',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'track-builder.condition.index',
                        'label' => 'Conditions',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'track-builder.condition.create',
                                'label' => 'Create',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.condition.show',
                                'label' => 'Show',
                                'type' => 'route-link-item',
                                'children' => [
                                    [
                                        'name' => 'track-builder.condition.edit',
                                        'label' => 'Edit',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.condition.delete',
                                        'label' => 'Delete',
                                        'type' => 'route-link-item',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'track-builder.survey-maintenance.index',
                        'label' => 'Surveys',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'track-builder.survey-maintenance.create',
                                'label' => 'Create',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.survey-maintenance.show',
                                'label' => 'Show',
                                'type' => 'route-link-item',
                                'children' => [
                                    [
                                        'name' => 'track-builder.survey-maintenance.edit',
                                        'label' => 'Edit',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.delete',
                                        'label' => 'Delete',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.check',
                                        'label' => 'Check all is answered',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.answer-import',
                                        'label' => 'Import answers',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.export-codebook',
                                        'label' => 'Update to new survey',
                                        'type' => 'route-link-item',
                                    ],
                                ],
                            ],
                            [
                                'name' => 'track-builder.survey-maintenance.check-all',
                                'label' => 'Check all is answered',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.survey-maintenance.answer-imports',
                                'label' => 'Import answers',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.survey-maintenance.update-survey',
                                'label' => 'Update to new survey',
                                'type' => 'route-link-item',
                            ],
                        ],
                    ],
                    [
                        'name' => 'track-builder.track-maintenance.index',
                        'label' => 'Tracks',
                        'type' => 'route-link-item',
                        'children' => [
                            [
                                'name' => 'track-builder.track-maintenance.create',
                                'label' => 'Create',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.track-maintenance.show',
                                'label' => 'Show',
                                'type' => 'route-link-item',
                                'children' => [
                                    [
                                        'name' => 'track-builder.track-maintenance.edit',
                                        'label' => 'Edit',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.delete',
                                        'label' => 'Delete',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.export',
                                        'label' => 'Export',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.check-track',
                                        'label' => 'Check rounds',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.recalc-fields',
                                        'label' => 'Recalculate fields',
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.track-fields',
                                        'label' => 'Fields',
                                        'type' => 'route-link-item',
                                        'children' => [
                                            [
                                                'name' => 'track-builder.track-fields.create',
                                                'label' => 'Create',
                                                'type' => 'route-link-item',
                                            ],
                                            [
                                                'name' => 'track-builder.track-fields.show',
                                                'label' => 'Show',
                                                'type' => 'route-link-item',
                                                'children' => [
                                                    [
                                                        'name' => 'track-builder.track-fields.edit',
                                                        'label' => 'Edit',
                                                        'type' => 'route-link-item',
                                                    ],
                                                    [
                                                        'name' => 'track-builder.track-fields.delete',
                                                        'label' => 'Delete',
                                                        'type' => 'route-link-item',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.track-rounds',
                                        'label' => 'Rounds',
                                        'type' => 'route-link-item',
                                        'children' => [
                                            [
                                                'name' => 'track-builder.track-rounds.create',
                                                'label' => 'Create',
                                                'type' => 'route-link-item',
                                            ],
                                            [
                                                'name' => 'track-builder.track-rounds.show',
                                                'label' => 'Show',
                                                'type' => 'route-link-item',
                                                'children' => [
                                                    [
                                                        'name' => 'track-builder.track-rounds.edit',
                                                        'label' => 'Edit',
                                                        'type' => 'route-link-item',
                                                    ],
                                                    [
                                                        'name' => 'track-builder.track-rounds.delete',
                                                        'label' => 'Delete',
                                                        'type' => 'route-link-item',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'track-builder.track-maintenance.track-overview',
                                'label' => 'Track per org',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.track-maintenance.check-all',
                                'label' => 'Check all rounds',
                                'type' => 'route-link-item',
                            ],
                            [
                                'name' => 'track-builder.track-maintenance.recalc-all-fields',
                                'label' => 'Recalculate all fields',
                                'type' => 'route-link-item',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
