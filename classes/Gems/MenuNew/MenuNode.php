<?php

namespace Gems\MenuNew;

abstract class MenuNode
{
    /** @var MenuItem[] */
    protected array $children = [];

    abstract protected function getMenu(): Menu;

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

    public function renderNode(): string
    {
        $renderedItem = $this->renderContent();

        $children = [];
        foreach ($this->children as $child) {
            if ($child->isOpen()) {
                $children[] = $child;
            }
        }

        return $this->getMenu()->templateRenderer->render('menu::menu-node', [
            'renderedItem' => $renderedItem,
            'children' => $children,
        ]);
    }
}
