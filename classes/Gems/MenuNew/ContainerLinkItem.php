<?php

namespace Gems\MenuNew;

class ContainerLinkItem extends RouteLinkItem
{
    protected string $itemTemplate = 'menu::container-link-item';

    protected function hasAccess(): bool
    {
        foreach($this->children as $child) {
            $access = $this->getMenu()->routeHelper->hasAccessToRoute($child->name);
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
