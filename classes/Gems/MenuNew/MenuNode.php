<?php

namespace Gems\MenuNew;

abstract class MenuNode
{
    /** @var MenuItem[] */
    protected array $children = [];

    abstract protected function getMenu(): Menu;

    abstract public function renderNode(): string;

    public function add(MenuItem $menuItem): void
    {
        $menuItem->attachParent($this);
        $this->children[] = $menuItem;
        $menuItem->register();
    }

    /**
     * @return \Gems\MenuNew\MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
