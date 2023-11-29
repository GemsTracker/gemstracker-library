<?php

namespace Gems\Handlers;

use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\SnippetsActions\Vue\CreateAction;
use Gems\SnippetsActions\Vue\EditAction;
use Zalt\SnippetsActions\SnippetActionInterface;

abstract class VueBrowseChangeHandler extends BrowseChangeHandler
{
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'create'     => CreateAction::class,
        'show'       => ShowAction::class,
        'edit'       => EditAction::class,
        'export'     => ExportAction::class,
        'delete'     => DeleteAction::class,
    ];

    protected string $dataEndpoint;

    protected string|null $dataResource = null;

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof CreateAction) {
            $action->dataEndpoint = $this->dataEndpoint;
            if ($this->dataResource !== null) {
                $action->dataResource = $this->dataResource;
            } else {
                $model = $this->getModel($action);
                $action->dataResource = $model->getName();
            }
        }
    }
}