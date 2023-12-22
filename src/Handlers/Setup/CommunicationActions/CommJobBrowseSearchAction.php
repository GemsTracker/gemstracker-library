<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Browse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup\CommunicationActions;

use Gems\Snippets\AutosearchFormSnippet;
use Gems\Snippets\Communication\CommInfoSnippet;
use Gems\Snippets\Communication\CommJobIndexButtonRowSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Browse
 * @since      Class available since version 1.0
 */
class CommJobBrowseSearchAction extends \Gems\SnippetsActions\Browse\BrowseSearchAction
{
    protected array $_startSnippets = [
        ContentTitleSnippet::class,
        AutosearchFormSnippet::class,
    ];

    protected array $_stopSnippets = [
        CommJobIndexButtonRowSnippet::class,
        CommInfoSnippet::class,
    ];

    /**
     * @var array Fields names => emptyname to search on
     */
    public array $searchFields = [];
}