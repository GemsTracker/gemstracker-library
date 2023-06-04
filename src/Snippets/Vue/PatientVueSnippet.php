<?php

namespace Gems\Snippets\Vue;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class PatientVueSnippet extends SnippetAbstract
{
    protected string $appId = 'app';

    protected int $organizationId;

    protected string $patientNr;

    protected $tag;

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

    public function getHtmlOutput()
    {
        $attributes = [
            'base-url' => '/',
            'api-url' => '/api',
            'patient-nr' => $this->patientNr,
            ':organization-id' => $this->organizationId,
            'locale' => $this->locale->getCurrentLanguage(),
            ...$this->vueOptions,
        ];

        $container = Html::div(['id' => $this->appId]);
        $app = Html::create($this->tag, $attributes);

        $container->append($app);

        return $container;
    }

    public function hasHtmlOutput(): bool
    {
        $attributes = $this->requestInfo->getRequestMatchedParams();
        if (!isset($attributes[Model::REQUEST_ID1], $attributes[Model::REQUEST_ID2])) {
            return false;
        }
        $this->patientNr = (string) $attributes[Model::REQUEST_ID1];
        $this->organizationId = (int) $attributes[Model::REQUEST_ID2];
        $this->layoutSettings->addResource('resource/js/gems-vue.js');
        return parent::hasHtmlOutput();
    }
}