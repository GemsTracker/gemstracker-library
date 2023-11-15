<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Browse;

use Zalt\SnippetsActions\NoCsrfInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 2.0
 */
class FastBrowseFilteredAction extends BrowseFilteredAction implements NoCsrfInterface
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
