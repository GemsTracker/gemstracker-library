<?php

namespace Gems\SnippetsActions\Vue;

use Gems\Snippets\Vue\VueSnippet;
use Zalt\SnippetsActions\AbstractAction;

class VueAction extends AbstractAction
{
    protected array $_snippets = [
        VueSnippet::class,
    ];

    public string $tag;

    public array $vueOptions = [];
}