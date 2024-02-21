<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Show;

use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\Usage\UsageSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Gems\SnippetsActions\ContentTitleActionTrait;
use Zalt\Model\MetaModellerInterface;
use Zalt\SnippetsActions\Browse\BrowseTableAction;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Show
 * @since      Class available since version 1.9.2
 */
class ShowAsTableAction extends BrowseFilteredAction
{
    public array $menuEditRoutes = [];

    public array $menuShowRoutes = [];

    public function isDetailed() : bool
    {
        return true;
    }
}