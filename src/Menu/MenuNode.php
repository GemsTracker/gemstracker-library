<?php

namespace Gems\Menu;

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
     * @return \Gems\Menu\MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
