<?php

namespace Gems\Menu;

class ContainerLinkItem extends RouteLinkItem
{
    protected string $itemTemplate = 'menu::container-link-item';

    protected function hasAccess(): bool
    {
        foreach($this->children as $child) {
            $access = $child->hasAccess();
            if ($access) {
                return true;
            }
        }

        return false;
    }

    public function renderNode(): string
    {
        foreach ($this->children as $child) {
            $child->open([]);
        }

        return parent::renderNode();
    }
}
