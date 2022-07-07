<?php

namespace Gems\MenuNew;

abstract class MenuItem extends MenuNode
{
    private ?Menu $menu = null;
    private ?MenuNode $parent = null;
    private bool $open = false;
    private bool $active = false;

    abstract protected function register();

    protected function getMenu(): Menu
    {
        return $this->menu;
    }

    protected function attachParent(MenuNode $parent): void
    {
        $this->parent = $parent;
        $this->menu = $parent->getMenu();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function openPath(array $params): void
    {
        $this->open($params);
        $this->setActive(true);

        if ($this->parent instanceof MenuItem) {
            $this->parent->openPath($params);
        }

        foreach ($this->children as $child) {
            if (!$child->isOpen()) {
                $child->open($params);
            }
        }
    }

    public function open(array $params): void
    {
        $this->open = true;
    }

    public function renderNode(): string
    {
        if (!$this->isOpen()) {
            return '';
        }

        return parent::renderNode();
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
