<?php

namespace Gems\Layout;

class LayoutSettings
{
    protected array $layoutParams = [];

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

    public function addLayoutParameter(string $key, mixed $value)
    {
        $this->layoutParams[$key] = $value;
        return $this;
    }

    public function addLayoutParameters(array $params)
    {
        $this->layoutParams = $params + $this->layoutParams;
        return $this;
    }

    /**
     * @param string $resource Javascript (vite) resource
     * @return void
     */
    public function addResource(string $resource): void
    {
        $this->resources[] = $resource;
    }

    public function addVue(array $settings = []): void
    {
        $template = $settings['template'] ?? 'gems::vue';
        $resource = $settings['resource'] ?? 'resource/js/gems-vue.js';

        $this->setTemplate($template);

        foreach((array)$resource as $resourceItem) {
            $this->addResource($resourceItem);
        }
    }

    public function enableMenu(): void
    {
        $this->showMenu = true;
    }

    public function disableMenu(): void
    {
        $this->showMenu = false;
    }

    public function getLayoutParameters(): array
    {
        return $this->layoutParams;
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