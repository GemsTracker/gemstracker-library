<?php

namespace Gems\Snippets\Vue;

use Zalt\Model\MetaModelInterface;

class BrowseSnippet extends VueSnippetAbstract
{
    /**
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected bool $createData = false;

    protected string $dataEndpoint;

    protected string $dataResource;

    protected array $headers;

    protected string $tag = 'data-table';

    protected function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['resource'] = $this->getDataResource();
        $attributes['endpoint'] = $this->getDataEndpoint();
        $attributes[':headers'] = $this->getHeaders();

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

    protected function getHeaders(): string
    {
        return json_encode($this->headers);
    }
}