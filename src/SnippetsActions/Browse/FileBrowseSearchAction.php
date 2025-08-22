<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 */

namespace Gems\SnippetsActions\Browse;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 2.x
 */
class FileBrowseSearchAction extends BrowseSearchAction
{
    /**
     * Optional extra sort(s)
     * @var array
     */
    public array $extraSort = ['changed' => SORT_DESC];

    public array $menuEditRoutes = ['download'];
}