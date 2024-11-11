<?php

namespace Gems\Snippets\Vue;

use Gems\Html;
use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Mezzio\Helper\UrlHelper;
use Zalt\Base\RequestInfo;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class VueSnippetAbstract extends SnippetAbstract
{
    protected string $appId = 'app';

    protected string $attributePrefix = 'data-vue-';

    protected string $tag;

    protected array $vueOptions = [];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected readonly LayoutSettings $layoutSettings,
        protected readonly Locale $locale,
        protected readonly UrlHelper $urlHelper,
        array $config,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
        $vueSettings = $config['vue'] ?? [];

        $this->layoutSettings->addResource($vueSettings['resource']);
        if (isset($vueSettings['style'])) {
            $this->layoutSettings->addResource($vueSettings['style']);
        }
    }

    protected function getApiUrl(): string
    {
        return rtrim($this->urlHelper->getBasePath(), '/')  . '/' . 'api';
    }

    public function getHtmlOutput()
    {
        $attributes = $this->prefixAttributes($this->getAttributes());
        $attributes['id'] = $this->appId;

        return Html::div($attributes);
    }

    protected function getAttributes(): array
    {
        $parameters = [
            'base-url' => rtrim($this->urlHelper->getBasePath(), '/')  . '/',
            'api-url' => $this->getApiUrl(),
            'locale' => $this->locale->getLanguage(),
            ...$this->vueOptions,
        ];

        if (isset($this->tag)) {
            $parameters['tag'] = $this->tag;
        }

        return $parameters;
    }

    protected function prefixAttributes(array $attributes): array
    {
        $prefixedAttributes = [];
        foreach($attributes as $key => $value) {
            $newKey = $this->attributePrefix . $key;
            $prefixedAttributes[$newKey] = $value;
        }
        return $prefixedAttributes;
    }
}