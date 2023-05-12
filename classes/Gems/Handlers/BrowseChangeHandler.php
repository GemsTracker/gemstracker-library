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
     * @var string[classname|SnippetActionInterface]
     */
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'create'     => CreateAction::class,
        'show'       => ShowAction::class,
        'edit'       => EditAction::class,
        'export'     => ExportAction::class,
        'delete'     => DeleteAction::class,
        ];
}