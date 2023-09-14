<?php

namespace Gems\SnippetsActions\Vue;

use Gems\Snippets\Vue\CreateEditSnippet;

class CreateAction extends \Gems\SnippetsActions\Form\CreateAction
{
    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        CreateEditSnippet::class,
    ];

    public string $dataEndpoint;

    public string $dataResource;
}