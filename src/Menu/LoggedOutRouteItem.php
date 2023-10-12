<?php

namespace Gems\Menu;

class LoggedOutRouteItem extends RouteLinkItem
{
    public function __construct(
        string $name,
        string $label,
        private readonly bool $isLoggedIn = false,
    )
    {
        parent::__construct($name, $label);
    }

    public function hasAccess(): bool
    {
        return !$this->isLoggedIn && parent::hasAccess();
    }
}
