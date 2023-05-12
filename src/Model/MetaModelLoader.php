<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\Sql\Expression;
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
     * Add database translation edit to model
     *
     * @param MetaModelInterface $model
     */
    public function addDatabaseTranslationEditFields(MetaModelInterface $metaModel): void
    {
        if ($this->modelConfig['translateDatabaseFields']) {
            $transformer = $this->createTransformer('Transform\\TranslateFieldEditor');
            $metaModel->addTransformer($transformer);
        }
    }

    /**
     * Add database translations to a model
     *
     * @param MetaModelInterface $model
     */
    public function addDatabaseTranslations(MetaModelInterface $metaModel): void
    {
        if ($this->modelConfig['translateDatabaseFields']) {
            $transformer = $this->createTransformer('Transform\\TranslateDatabaseFields');
            $metaModel->addTransformer($transformer);
        }
    }

    protected function setChangeField(MetaModelInterface $metaModel, string $fieldName, mixed $defaultValue, bool $createdOnly)
    {
        if ($metaModel->has($fieldName)) {
            $metaModel->set($fieldName, ['default' => $defaultValue, 'elementClass' => 'none']);
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