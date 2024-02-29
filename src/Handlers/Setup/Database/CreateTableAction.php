<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Snippets\Database\CreateTableSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\ModelActionTrait;
use Zalt\SnippetsActions\ParameterActionInterface;

class CreateTableAction extends AbstractAction implements ModelActionInterface, ParameterActionInterface
{
    use ModelActionTrait;

    protected array $_snippets = [
        CreateTableSnippet::class,
    ];

    public function isEditing(): bool
    {
        return true;
    }
}