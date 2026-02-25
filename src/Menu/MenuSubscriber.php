<?php

declare(strict_types=1);

namespace Gems\Menu;

use Gems\Event\Application\CreateMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zalt\Base\TranslatorInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    use HandlerMenuTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly array $config,
    )
    {
        $this->translate = $this->translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            CreateMenuEvent::class => [
                ['updateMenu', 100],
            ],
        ];
    }

    public function updateMenu(CreateMenuEvent $event)
    {
        $menu = $event->getMenu();

        if (isset($this->config['survey']['paper-answers']) && $this->config['survey']['paper-answers'] === true) {
            $menu->addFromConfig($menu, [
                $this->createMenuItem(
                    name: 'respondent.tracks.token.answered-on-paper',
                    label: $this->translator->trans('Answered on paper'),
                    parent: 'respondent.tracks.token.show',
                ),
            ]);
        }
    }
}