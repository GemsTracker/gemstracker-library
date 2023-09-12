<?php

namespace Gems\SnippetsActions\Vue;

use Gems\Snippets\Vue\PatientVueSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ParameterActionInterface;

class VueAction extends AbstractAction implements ParameterActionInterface
{
    protected array $_snippets = [
        PatientVueSnippet::class,
    ];

    public string $tag;

    public array $vueOptions = [];
}