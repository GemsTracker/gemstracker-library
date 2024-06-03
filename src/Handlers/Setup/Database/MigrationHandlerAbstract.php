<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Db\Migration\MigrationModelFactory;
use Gems\Db\Migration\MigrationRepositoryAbstract;
use Gems\Handlers\BrowseChangeHandler;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\CreateModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

abstract class MigrationHandlerAbstract extends BrowseChangeHandler
{
    use CreateModelHandlerTrait;

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly MigrationModelFactory $migrationModelFactory,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    abstract protected function getRepository(): MigrationRepositoryAbstract;

    protected function createModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $repository = $this->getRepository();
        $model = $this->migrationModelFactory->createModel($repository);
        $metaModel = $model->getMetaModel();

        $order = 0;

        $metaModel->set('name', [
            'label' => $this->_('Name'),
            'order' => $order++,
        ]);

        $metaModel->set('module', [
            'label' => $this->_('Group'),
            'order' => $order++,
        ]);

        $metaModel->set('status', [
            'label' => $this->_('Status'),
            'order' => $order++,
        ]);

        $metaModel->set('db', [
            'label' => $this->_('Database'),
            'order' => $order++,
        ]);

        if ($action->isDetailed()) {
            $metaModel->set('description', [
                'label' => $this->_('Description'),
                'order' => $order++,
            ]);
            $metaModel->set('comment', [
                'label' => $this->_('Comment'),
                'order' => $order++,
            ]);
            $metaModel->set('lastChanged', [
                'label' => $this->_('Changed'),
                'order' => $order++,
            ]);
            $metaModel->set('sql', [
                'label' => $this->_('Data'),
                'order' => $order++,
                'itemDisplay' => 'pre',
            ]);
        }

        return $model;
    }
}