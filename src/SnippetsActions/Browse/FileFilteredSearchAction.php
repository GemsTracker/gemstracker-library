<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Browse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Browse;

use Gems\SnippetsActions\Browse\BrowseFilteredAction;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Browse
 * @since      Class available since version 1.0
 */
class FileFilteredSearchAction extends BrowseFilteredAction
{
    /**
     * Optional extra sort(s)
     * @var array
     */
    public array $extraSort = ['changed' => SORT_DESC];

    public array $menuEditRoutes = ['download'];
}