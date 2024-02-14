<?php

/**
 *
 * @package    Gems
 * @subpackage TrackAllAnswersSnippet
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Answers;

use Zalt\Model\Data\DataReaderInterface;

/**
 *
 * @package    Gems
 * @subpackage TrackAllAnswersSnippet
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class TrackAllAnswersModelSnippet extends TrackAnswersModelSnippet
{
    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        $model = parent::createModel();
        $metaModel = $model->getMetaModel();

        foreach ($metaModel->getItemNames() as $name) {
            if (! $metaModel->has($name, 'label')) {
                $metaModel->set($name, 'label', ' ');
            }
        }

        return $model;
    }
}
