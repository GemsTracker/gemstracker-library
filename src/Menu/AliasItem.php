<?php

namespace Gems\Menu;

/**
 * An invisible menu item. If the route associated with this menu item is
 * currently active, the menu will not show this item but show the aliased
 * item as active instead.
 */
class AliasItem extends MenuItem
{
    public function __construct(
        public readonly string $name,
        public readonly string $alias,
        public readonly string $label = '',
    ) {
    }

    protected function register()
    {
        $this->getMenu()->registerItem($this->name, $this);
    }

    protected function hasAccess(): bool
    {
        return false;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function renderNode(): string
    {
        return '';
    }

    public function setLabel(string $label): void
    {
    }
}
