<?php

declare(strict_types=1);

namespace Gems\Handlers\Export;

use Gems\Export\Db\FileExportDownloadModel;
use Gems\Handlers\BrowseChangeHandler;
use Gems\Handlers\GemsHandler;
use Gems\Snippets\Export\ExportDownloadSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Show\ShowAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class ExportDownloadsHandler extends BrowseChangeHandler
{

    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'show'       => ShowAction::class,
        'delete'     => DeleteAction::class,
    ];

    public static array $parameters = ['id' => '[a-z0-9A-Z\.-]+',];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly FileExportDownloadModel $fileExportDownloadModel,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    public function getIndexTitle(): string
    {
        return $this->_('Export downloads');
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        return $this->fileExportDownloadModel;
    }

    public function getTopic(int $count = 1): string
    {
        return $this->plural('export download', 'export downloads', $count);
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);
        if ($action instanceof BrowseFilteredAction) {
            $action->setSnippets([ExportDownloadSnippet::class]);
            $action->menuEditRoutes = ['delete'];
        }
    }
}