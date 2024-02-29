<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Snippets\Database\CreateTableSnippet;
use Gems\Snippets\Database\CreateTablesSnippet;
use Gems\Snippets\Database\RunAllPatchesSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\ModelActionTrait;
use Zalt\SnippetsActions\ParameterActionInterface;

class RunAllPatchesAction extends AbstractAction implements ModelActionInterface
{
    use ModelActionTrait;

    protected array $_snippets = [
        RunAllPatchesSnippet::class,
    ];
}