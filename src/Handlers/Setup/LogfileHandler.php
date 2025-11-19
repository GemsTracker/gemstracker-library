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
use Gems\SnippetsActions\Browse\FileBrowseSearchAction;
use Gems\SnippetsActions\Browse\FileFilteredSearchAction;
use Gems\SnippetsActions\Download\DownloadFileAction;
use Gems\SnippetsActions\Show\ShowAction;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Finder\Finder;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

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
        'autofilter' => FileFilteredSearchAction::class,
        'download'   => DownloadFileAction::class,
        'index'      => FileBrowseSearchAction::class,
        'show'       => ShowAction::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly array $config,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    /**
     * @inheritDoc
     */
    protected function createModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $finder = new Finder();
        $finder->files()->in([$this->getDirectory()]);
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

    protected function getDirectory(): string
    {
        return $this->config['logDir'];
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof DownloadFileAction) {
            $action->directory = $this->getDirectory();
        }
    }
}
