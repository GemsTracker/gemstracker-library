<?php

namespace Gems\Auth\Acl;

use Gems\Event\Application\RoleGatherPrivilegeDropsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            RoleGatherPrivilegeDropsEvent::class => [
                ['gatherPrivilegeDrops'],
            ],
        ];
    }

    public function gatherPrivilegeDrops(RoleGatherPrivilegeDropsEvent $event): void
    {
        if (!$this->config['account']['edit-auth']['enabled']) {
            $event->dropPrivileges(['option.edit-auth']);
        }
    }
}
