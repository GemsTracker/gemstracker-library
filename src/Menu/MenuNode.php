<?php

namespace Gems\Menu;

abstract class MenuNode
{
    /** @var MenuItem[] */
    protected array $children = [];

    abstract protected function getMenu(): Menu;

    abstract public function renderNode(): string;

    public function add(MenuItem $menuItem, int|string|null $position = null): void
    {
        $menuItem->attachParent($this);

        $index = $this->getAddPosition($position);
        array_splice($this->children, $index, 0, [$menuItem]);

        $menuItem->register();
    }

    /**
     * @return \Gems\Menu\MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    private function getAddPosition(int|string|null $position = null): int
    {
        if ($position === null) {
            return count($this->getChildren());
        }
        if (is_int($position)) {
            return $position;
        }


        foreach(array_values($this->getChildren()) as $key => $child) {
            if ($child->name === $position) {
                return $key + 1;
            }
        }

        return count($this->getChildren());
    }
}
