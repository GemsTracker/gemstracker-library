<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\GemsHandler;
use Gems\Model\Ra\FolderModel;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Download\DownloadFileAction;
use Gems\SnippetsActions\Show\ShowAction;
use Symfony\Component\Finder\Finder;
use Zalt\Model\MetaModellerInterface;
use Zalt\SnippetsActions\SnippetActionInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 2.x
 */
class LogfileHandler extends GemsHandler
{
    use \Zalt\SnippetsHandler\CreateModelHandlerTrait;

    /**
     * @inheritdoc
     */
    public static $actions = [
        'download'   => DownloadFileAction::class,
        'index'      => BrowseSearchAction::class,
        'show'       => ShowAction::class,
    ];

    /**
     * @inheritDoc
     */
    protected function createModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $finder = new Finder();
        $finder->files()->in('/app/data/logs'); // FIXME
        $model = $this->metaModelLoader->createModel(FolderModel::class, $finder);

        $metaModel = $model->getMetaModel();
        $metaModel->getKeys();
        $metaModel->resetOrder();

        $metaModel->set('filename', [
            'label' => $this->_('Filename'),
            'key' => true,
        ]);
        $metaModel->set('size', [
            'label' => $this->_('Size'),
        ]);
        $metaModel->set('changed', [
            'label' => $this->_('Changed'),
        ]);

        if ($action->isDetailed()) {
            $metaModel->set('content', [
                'label' => $this->_('Content'),
                'itemDisplay' => 'pre',
            ]);
        }

        return $model;
    }
}
