<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Gems\Config\ConfigAccessor;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Respondent\RespondentModel;
use Gems\Model\Transform\TranslateDatabaseFields;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.9.2
 */
class MetaModelLoader extends \Zalt\Model\MetaModelLoader
{
    public function __construct(
        ProjectOverloader $loader,
        array $modelConfig,
        protected CurrentUserRepository $currentUserRepository,
    )
    {
        parent::__construct($loader, $modelConfig);
    }
    
    /**
     * Add database translations to a model
     *
     * @param MetaModelInterface $model
     */
    public function addDatabaseTranslations(MetaModelInterface $metaModel, bool $detailed = false, ?array $config = null): void
    {
        if ($this->modelConfig['translateDatabaseFields']) {
            if ($config) {
                $transformer = $this->createTransformer('Transform\\TranslateDatabaseFields', $detailed, $config);
            } else {
                $transformer = $this->createTransformer('Transform\\TranslateDatabaseFields', $detailed);
            }
            $metaModel->addTransformer($transformer);
        }
    }

    public function createJoinModel(string $startTable, string $modelName = null, bool $savable = true): GemsJoinModel
    {
        if ($modelName === null) {
            $modelName = $startTable;
        }

        /**
         * @var GemsJoinModel
         */
        return $this->loader->create(GemsJoinModel::class, $startTable, $modelName, $savable);
    }

    public function createTableModel(string $table): SqlTableModel
    {
        /**
         * @var SqlTableModel
         */
        return $this->loader->create(SqlTableModel::class, $table);
    }

    public function createUnionModel(string $name, string $modelField = 'sub'): UnionModel
    {
        /**
         * @var UnionModel
         */
        return $this->loader->create(UnionModel::class, $name, $modelField);
    }

    public function getRespondentModel(): RespondentModel
    {
        return $this->loader->getContainer()->get(RespondentModel::class);
    }

    protected function setChangeField(MetaModelInterface $metaModel, string $fieldName, mixed $defaultValue, bool $createdOnly)
    {
        if ($metaModel->has($fieldName)) {
            $metaModel->set($fieldName, ['default' => $defaultValue, 'elementClass' => 'None', 'changeField' => true]);
            $metaModel->setOnSave($fieldName, $defaultValue);
            if ($createdOnly) {
                $metaModel->setSaveWhenNew($fieldName);
            } else {
                $metaModel->setSaveOnChange($fieldName);
            }
        }
    }

    public function setChangeFields(MetaModelInterface $metaModel, string $prefix)
    {
        $currentTimestamp = date('Y-m-d H:i:s');
        $userId           = $this->currentUserRepository->getCurrentUserId();

        $this->setChangeField($metaModel, $prefix . '_changed', $currentTimestamp, false);
        $this->setChangeField($metaModel, $prefix . '_changed_by', $userId, false);
        $this->setChangeField($metaModel, $prefix . '_created', $currentTimestamp, true);
        $this->setChangeField($metaModel, $prefix . '_created_by', $userId, true);
    }
}