<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.9.2
 */
trait ButtonRowActiontrait
{
    /**
     * Add the children of the current menu item to the button row
     * @var boolean
     */
    public bool $addCurrentChildren = true;

    /**
     * Add the parent of the current menu item to the button row
     * @var boolean
     */
    public bool $addCurrentParent = true;

    /**
     * Add the siblings of the current menu item to the button row
     * @var boolean
     */
    public bool $addCurrentSiblings = false;

    /**
     * @var array An array of routes to add to the button row
     */
    public array $extraRoutes = [];

    /**
     * @var array An array of route => label to add to the button row
     */
    public array $extraRoutesLabelled = [];

    /**
     * @var string|null The label of the parent menu item button [instead of Cancel]
     */
    protected ?string $parentLabel = null;
}