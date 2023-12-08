<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Browse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup\CommunicationActions;

use Gems\Snippets\Agenda\AutosearchFormSnippet;
use Gems\Snippets\Communication\CommInfoSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;

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
        CurrentButtonRowSnippet::class,
        CommInfoSnippet::class,
    ];

    /**
     * @var array Fields names => emptyname to search on
     */
    public array $searchFields = [];
}