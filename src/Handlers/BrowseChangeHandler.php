<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\SnippetsActions\Show\ShowAction;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.9.2
 */
abstract class BrowseChangeHandler extends GemsHandler
{
    /**
     * The order is important: if the regex of a parameter allows export / create / delete / edit these action routes
     * must be defined before the parameter is added to prevent an "is shadowed by previously defined variable route"
     * exception!
     *
     * @var string[classname|SnippetActionInterface]
     */
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'create'     => CreateAction::class,
        'export'     => ExportAction::class,
        'edit'       => EditAction::class,
        'delete'     => DeleteAction::class,
        'show'       => ShowAction::class,
        ];
}