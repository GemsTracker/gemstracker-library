<?php

namespace Gems\Snippets\Vue;

use Gems\Html;
use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Zalt\Base\RequestInfo;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class VueSnippetAbstract extends SnippetAbstract
{
    protected string $appId = 'app';

    protected string $tag;

    protected array $vueOptions = [];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected readonly  LayoutSettings $layoutSettings,
        protected readonly TemplateRendererInterface $templateRenderer,
        protected readonly Locale $locale,
        protected readonly UrlHelper $urlHelper,
        array $config,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
        $vueSettings = $config['vue'] ?? [];

        if (isset($vueSettings['default'])) {
            $resource = 'resource/js/' . $vueSettings['default'];
            $this->layoutSettings->addResource($resource);
        }
    }

    protected function getApiUrl(): string
    {
        return $this->urlHelper->getBasePath() . 'api';
    }

    public function getHtmlOutput()
    {
        $attributes = $this->getAttributes();

        $container = Html::div(['id' => $this->appId]);
        $app = Html::create($this->tag, $attributes);

        $container->append($app);

        return $container;
    }

    protected function getAttributes(): array
    {
        $parameters = [
            'base-url' => $this->urlHelper->getBasePath(),
            'api-url' => $this->getApiUrl(),
            'locale' => $this->locale->getLanguage(),
            ...$this->vueOptions,
        ];

        return $parameters;
    }
}