<?php

namespace Gems\Event\Application;

use Symfony\Contracts\EventDispatcher\Event;

class RoleGatherPrivilegeDropsEvent extends Event
{
    private array $droppedPrivileges = [];

    public function dropPrivileges(array $privileges): void
    {
        $this->droppedPrivileges = array_merge($this->droppedPrivileges, $privileges);
    }

    public function getDroppedPrivileges(): array
    {
        return $this->droppedPrivileges;
    }
}
