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

class CreateEditSnippet extends VueSnippetAbstract
{
    /**
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected bool $createData = false;

    protected string $dataEndpoint;

    protected string $dataResource;

    protected string $formType = 'horizontal';

    protected ?string $submitLabel = null;

    protected string $tag = 'gems-form';

    protected function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['resource'] = $this->getDataResource();
        $attributes['endpoint'] = $this->getDataEndpoint();
        $attributes['form-type'] = $this->formType;

        if ($this->createData === false) {
            $attributes['edit'] = $this->requestInfo->getParam(Model::REQUEST_ID);
        }

        if ($this->submitLabel) {
            $attributes['submit-label'] = $this->submitLabel;
        }

        return $attributes;
    }

    protected function getDataEndpoint(): string
    {
        return $this->dataEndpoint;
    }

    protected function getDataResource(): string
    {
        return $this->dataResource;
    }
}