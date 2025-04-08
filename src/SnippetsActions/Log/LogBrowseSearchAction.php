<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Log;

use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Log\LogSearchSnippet;
use Gems\Snippets\Log\LogTableSnippet;
use Gems\SnippetsActions\Browse\FastBrowseSearchAction;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Log
 * @since      Class available since version 1.0
 */
class LogBrowseSearchAction extends FastBrowseSearchAction
{
    protected array $_startSnippets = [
        ContentTitleSnippet::class,
        LogSearchSnippet::class,
    ];

    protected array $_snippets = [
        LogTableSnippet::class,
    ];
}