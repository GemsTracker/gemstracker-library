<?php

namespace Gems\Snippets\Vue;

use Zalt\Model\MetaModelInterface;

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

    protected ?string $abortAction = 'index';

    protected string $tag = 'gems-form';

    protected function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['resource'] = $this->getDataResource();
        $attributes['endpoint'] = $this->getDataEndpoint();
        $attributes['form-type'] = $this->formType;

        if ($this->createData === false) {
            $attributes['edit'] = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID);
        }

        if ($this->submitLabel) {
            $attributes['submit-label'] = $this->submitLabel;
        }

	if ($this->abortAction) {
            $attributes['cancel-url'] = $this->menuSnippetHelper->getRelatedRouteUrl($this->abortAction);
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