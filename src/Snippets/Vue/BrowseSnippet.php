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

    protected int|null $perPage = null;

    protected array $rowClasses = [];

    protected array $searchStructure = [];

    protected string $tag = 'data-table';

    protected function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['resource'] = $this->getDataResource();
        $attributes['endpoint'] = $this->getDataEndpoint();
        $attributes[':headers'] = $this->getHeaders();
        $attributes[':search-structure'] = $this->getSearchStructure();
        if (isset($this->perPage)) {
            $attributes[':per-page'] = $this->perPage;
        }
        if (isset($this->rowClasses)) {
            $attributes[':row-classes'] = json_encode($this->rowClasses);
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

    protected function getHeaders(): string
    {
        return json_encode($this->headers);
    }

    protected function getSearchStructure(): string
    {
        return json_encode($this->searchStructure);
    }
}