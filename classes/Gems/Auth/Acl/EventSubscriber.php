<?php

namespace Gems\Auth\Acl;

use Gems\Event\Application\MenuBuildItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AclRepository $aclRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MenuBuildItemsEvent::class => [
                ['removeConfigRoleRoutes']
            ],
        ];
    }

    public function removeConfigRoleRoutes(MenuBuildItemsEvent $event): void
    {
        if ($this->aclRepository->hasRolesFromConfig()) {
            $event->removeItems([
                'setup.access.roles.create',
                'setup.access.roles.edit',
                'setup.access.roles.delete',
            ]);
        }
    }
}
