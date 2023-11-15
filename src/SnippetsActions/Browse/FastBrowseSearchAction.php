<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Browse;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 2.0
 */
class FastBrowseSearchAction extends BrowseSearchAction
{
    /**
     * Enable pagination.
     */
    public bool $browse = true;

    /**
     * But don't show totals.
     */
    public bool $showTotal = false;
}
