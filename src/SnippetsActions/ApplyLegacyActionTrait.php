<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions;

use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\SnippetsActions\Vue\CreateAction;
use Zalt\SnippetsActions\SnippetActionInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.0
 */
trait ApplyLegacyActionTrait
{
    public function applyStringAction(string $action, bool $detailed): void
    {
        $actionClass = $this->getStringActionClass($action, $detailed);
        $this->applyAction($actionClass);
    }

    public function getStringActionClass(string $action, bool $detailed): SnippetActionInterface
    {
        switch (strtolower(str_replace('-', '', $action))) {
            case 'changeconsent':
            case 'edit':
            case 'import':
            case 'simpleapi':
                return new EditAction();

            case 'create':
                return new CreateAction();

            case 'delete':
                return new DeleteAction();

            case 'export':
                return new ExportAction();

            case 'show':
                return new ShowAction();

            default:
                if ($detailed) {
                    return new ShowAction();
                }
                return new BrowseSearchAction();
        }
    }
}