<?php

namespace Gems\Db\Migration;

use Gems\Model\IteratorModel;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;

class MigrationModelFactory
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly MetaModelLoader $metaModelLoader,
    )
    {}

    public function createModel(MigrationRepositoryAbstract $migrationRepository): DataReaderInterface
    {
        /**
         * @var IteratorModel $model
         */
        $model = $this->metaModelLoader->createModel(IteratorModel::class, $migrationRepository->getModelName());
        $model->setData($migrationRepository->getInfo());
        $metaModel = $model->getMetaModel();

        $metaModel->set('module', [
            'maxlength' => 40,
            'type' => MetaModelInterface::TYPE_STRING,
        ]);

        $metaModel->set('id', [
            'key' => true,
            'maxlength' => 40,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('name', [
            'maxlength' => 40,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('type', [
            'maxlength' => 40,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('order', [
            'decimals' => 0,
            'default' => 1000,
            'maxlength' => 6,
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);
        $metaModel->set('description', [
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('data', [
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('sql', [
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('lastChanged', [
            'type' => MetaModelInterface::TYPE_DATETIME
        ]);
        $metaModel->set('location', [
            'maxlength' => 12,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('db', [
            'maxlength' => 32,
            'type' => MetaModelInterface::TYPE_STRING
        ]);
        $metaModel->set('status', [
            'multiOptions' => [
                'success' => $this->translator->_('Success'),
                'error' => $this->translator->_('Error'),
                'new' => $this->translator->_('New'),
            ],
        ]);

        return $model;
    }
}