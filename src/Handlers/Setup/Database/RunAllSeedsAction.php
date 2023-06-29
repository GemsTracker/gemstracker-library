<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Snippets\Database\CreateTableSnippet;
use Gems\Snippets\Database\CreateTablesSnippet;
use Gems\Snippets\Database\RunAllSeedsSnippet;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\ParameterActionInterface;

class RunAllSeedsAction extends AbstractAction implements ModelActionInterface
{
    protected array $_snippets = [
        RunAllSeedsSnippet::class,
    ];
}