<?php

namespace Gems\SnippetsActions\Vue;

use Gems\Snippets\Vue\CreateEditSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\NoCsrfInterface;
use Zalt\SnippetsActions\PostActionInterface;

class CreateAction extends AbstractAction implements PostActionInterface, NoCsrfInterface
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