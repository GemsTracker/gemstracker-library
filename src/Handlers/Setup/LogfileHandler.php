<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 */

namespace Gems\Handlers\Setup;

use ArrayObject;
use Gems\Handlers\GemsHandler;
use Gems\Model\Ra\FolderModel;
use Gems\Model\SqlTableModel;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Delete\DeleteAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\SnippetsActions\Show\ShowAction;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Finder\Finder;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Ra\PhpArrayModel;
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
        'index'      => BrowseSearchAction::class,
        'show'       => ShowAction::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly ProjectOverloader $projectOverloader,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    /**
     * @inheritDoc
     */
    protected function createModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $finder = new Finder('/app/data/logs'); // FIXME
        $dir = '/';
        $model = $this->projectOverloader->create(FolderModel::class, '/app/data/logs');
        //$model = $this->metaModelLoader->createModel(FolderModel::class, 'logfiles', $finder);

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

        return $model;
    }
}
