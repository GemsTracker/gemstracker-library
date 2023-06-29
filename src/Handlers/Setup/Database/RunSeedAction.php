<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Snippets\Database\CreateTableSnippet;
use Gems\Snippets\Database\RunSeedSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\ParameterActionInterface;

class RunSeedAction extends AbstractAction implements ModelActionInterface, ParameterActionInterface
{
    protected array $_snippets = [
        RunSeedSnippet::class,
    ];

    public function isEditing(): bool
    {
        return true;
    }
}