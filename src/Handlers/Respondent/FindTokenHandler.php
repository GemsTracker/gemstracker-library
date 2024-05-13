<?php

namespace Gems\Handlers\Respondent;

use Gems\Snippets\Vue\VueSnippet;
use Gems\SnippetsActions\Vue\VueAction;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\SnippetHandler;

class FindTokenHandler extends SnippetHandler
{
    public static $actions = [
        'index' => VueAction::class
    ];

    public function prepareAction(SnippetActionInterface $action): void
    {
        if ($action instanceof VueAction) {
            $action->setSnippets([
                VueSnippet::class
            ]);
            $action->tag = 'find-token';
        }
    }
}