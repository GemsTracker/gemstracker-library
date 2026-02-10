<?php

namespace Gems\SnippetsActions\Vue;

use Gems\Snippets\Vue\BrowseSnippet;
use Zalt\SnippetsActions\AbstractAction;

class BrowseAction extends AbstractAction
{
    protected array $_snippets = [
        BrowseSnippet::class,
    ];

    public string $dataEndpoint;

    public string $dataResource;

    public array $headers = [];

    public int|null $perPage = null;

    public array $rowClasses = [];

    public array $searchStructure = [];

    public string $tag;

    public array $vueOptions = [];
}