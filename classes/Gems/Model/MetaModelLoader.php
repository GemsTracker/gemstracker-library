<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.9.2
 */
class MetaModelLoader extends \Zalt\Model\MetaModelLoader
{
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
}