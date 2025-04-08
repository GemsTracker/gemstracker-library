<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Log;

use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Log\LogShowSnippet;
use Gems\SnippetsActions\Show\ShowAction;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Log
 * @since      Class available since version 1.0
 */
class LogShowAction extends ShowAction
{
    protected array $_snippets = [
        ContentTitleSnippet::class,
        LogShowSnippet::class,
        CurrentButtonRowSnippet::class,
    ];
}