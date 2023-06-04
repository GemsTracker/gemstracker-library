<?php

namespace Gems\Snippets\Vue;

use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class CreateEditSnippet extends SnippetAbstract
{
    protected $appId = 'app';

    /**
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected $createData = false;

    protected string $dataEndpoint;

    protected string $dataResource;

    protected $formType = 'horizontal';

    protected string $tag = 'gems-form';

    protected $vueOptions = [];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected LayoutSettings $layoutSettings,
        protected TemplateRendererInterface $templateRenderer,
        protected Locale $locale,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
    }

    protected function getDataEndpoint()
    {
        return $this->dataEndpoint;
    }

    protected function getDataResource()
    {
        return $this->dataResource;
    }

    public function getHtmlOutput()
    {
        $attributes = [
            'base-url' => '/',
            'api-url' => '/api',
            'resource' => $this->getDataResource(),
            'endpoint' => $this->getDataEndpoint(),
            'form-type' => $this->formType,
            'locale' => $this->locale->getCurrentLanguage(),
            ...$this->vueOptions,
        ];

        if ($this->createData === false) {
            $attributes['edit'] = $this->requestInfo->getParam(Model::REQUEST_ID);
        }

        $container = Html::div(['id' => $this->appId]);
        $app = Html::create($this->tag, $attributes);

        $container->append($app);

        $this->layoutSettings->addResource('resource/js/gems-vue.js');

        return $container;
    }

    public function hasHtmlOutput(): bool
    {

        return parent::hasHtmlOutput();
    }
}