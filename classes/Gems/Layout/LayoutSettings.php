<?php

namespace Gems\Layout;

class LayoutSettings
{
    /**
     * @var array Javascript (vite) resources
     */
    protected array $resources = [
        //'resource/js/general.js',
    ];

    /**
     * @var bool Should the menu be shown
     */
    protected bool $showMenu = true;

    /**
     * @var string template to show
     */
    protected string $template = 'gems::legacy-view';

    /**
     * @param string $resource Javascript (vite) resource
     * @return void
     */
    public function addResource(string $resource): void
    {
        $this->resources[] = $resource;
    }

    public function addVue(): void
    {
        $this->setTemplate('gems::vue');
        $this->addResource('resource/js/gems-vue.js');
    }

    public function enableMenu(): void
    {
        $this->showMenu = true;
    }

    public function disableMenu(): void
    {
        $this->showMenu = false;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template Twig template name
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * @return bool
     */
    public function showMenu(): bool
    {
        return $this->showMenu;
    }
}