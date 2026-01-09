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

    public string $tag;

    public array $vueOptions = [];
}