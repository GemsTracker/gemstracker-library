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
            [
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
                                        'name' => 'track-builder.source.attributes',
                                        'label' => $this->translator->trans('Check attributes'),
                                        'type' => 'route-link-item',
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
                                'name' => 'track-builder.survey-maintenance.create',
                                'label' => $this->translator->trans('Create'),
                                'type' => 'route-link-item',
                            ],
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
                                        'name' => 'track-builder.survey-maintenance.delete',
                                        'label' => $this->translator->trans('Delete'),
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.check',
                                        'label' => $this->translator->trans('Check all is answered'),
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.answer-import',
                                        'label' => $this->translator->trans('Import answers'),
                                        'type' => 'route-link-item',
                                    ],
                                    [
                                        'name' => 'track-builder.survey-maintenance.export-codebook',
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
                                'name' => 'track-builder.survey-maintenance.update-survey',
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
                                        'name' => 'track-builder.track-maintenance.track-fields',
                                        'label' => $this->translator->trans('Fields'),
                                        'type' => 'route-link-item',
                                        'children' => [
                                            [
                                                'name' => 'track-builder.track-fields.create',
                                                'label' => $this->translator->trans('Create'),
                                                'type' => 'route-link-item',
                                            ],
                                            [
                                                'name' => 'track-builder.track-fields.show',
                                                'label' => $this->translator->trans('Show'),
                                                'type' => 'route-link-item',
                                                'children' => [
                                                    [
                                                        'name' => 'track-builder.track-fields.edit',
                                                        'label' => $this->translator->trans('Edit'),
                                                        'type' => 'route-link-item',
                                                    ],
                                                    [
                                                        'name' => 'track-builder.track-fields.delete',
                                                        'label' => $this->translator->trans('Delete'),
                                                        'type' => 'route-link-item',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'track-builder.track-maintenance.track-rounds',
                                        'label' => $this->translator->trans('Rounds'),
                                        'type' => 'route-link-item',
                                        'children' => [
                                            [
                                                'name' => 'track-builder.track-rounds.create',
                                                'label' => $this->translator->trans('Create'),
                                                'type' => 'route-link-item',
                                            ],
                                            [
                                                'name' => 'track-builder.track-rounds.show',
                                                'label' => $this->translator->trans('Show'),
                                                'type' => 'route-link-item',
                                                'children' => [
                                                    [
                                                        'name' => 'track-builder.track-rounds.edit',
                                                        'label' => $this->translator->trans('Edit'),
                                                        'type' => 'route-link-item',
                                                    ],
                                                    [
                                                        'name' => 'track-builder.track-rounds.delete',
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
                                'name' => 'track-builder.track-maintenance.track-overview',
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
            ],
        ];
    }
}
