<?php

namespace Gems\Snippets\Vue;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class PatientVueSnippet extends VueSnippetAbstract
{
    protected int $organizationId;

    protected string $patientNr;

    protected function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['patient-nr'] = $this->patientNr;
        $attributes[':organization-id'] = $this->organizationId;

        return $attributes;
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
        return parent::hasHtmlOutput();
    }
}