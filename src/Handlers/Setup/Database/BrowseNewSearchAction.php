<?php

namespace Gems\Handlers\Setup\Database;

use Gems\SnippetsActions\Browse\BrowseSearchAction;

class BrowseNewSearchAction extends BrowseSearchAction
{
    public array $extraFilter = [
        'status' => ['new', 'error'],
    ];

    public array $menuEditRoutes = [
        'execute',
    ];
}