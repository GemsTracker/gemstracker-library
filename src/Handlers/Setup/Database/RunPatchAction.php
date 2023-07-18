<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Snippets\Database\CreateTableSnippet;
use Gems\Snippets\Database\RunPatchSnippet;
use Gems\Snippets\Database\RunSeedSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\ParameterActionInterface;

class RunPatchAction extends AbstractAction implements ModelActionInterface, ParameterActionInterface
{
    protected array $_snippets = [
        RunPatchSnippet::class,
    ];

    public function isEditing(): bool
    {
        return true;
    }
}