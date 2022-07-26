<?php

/**
 *
 * @package    Gems
 * @subpackage Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Import;

/**
 * \Gems specific importer class
 *
 * @package    Gems
 * @subpackage Import
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Importer extends \MUtil\Model\Importer
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @param string $idPart End part for batch id
     * @param \MUtil\Task\TaskBatch $batch Optional batch with different source etc..
     * @return \MUtil\Task\TaskBatch
     */
    protected function getBasicImportBatch($idPart, \MUtil\Task\TaskBatch $batch = null)
    {
        if (null === $batch) {
            $batch = $this->loader->getTaskRunnerBatch('check_' . basename($this->sourceModel->getName()) . '_' . $idPart);
        }
        $batch->setProgressTemplate($this->_('Remaining time: {remaining} - {msg}'));        
        
        return parent::getBasicImportBatch($idPart, $batch);
    }
    /**
     *
     * @param \MUtil\Task\TaskBatch $batch Optional batch with different source etc..
     * @return \MUtil\Task\TaskBatch
     */
    public function getImportOnlyBatch(\MUtil\Task\TaskBatch $batch = null)
    {
        if (! $this->_importBatch instanceof \MUtil\Task\TaskBatch) {
            $batch = $this->loader->getTaskRunnerBatch(__CLASS__ . '_import_' .
                    basename($this->sourceModel->getName()) . '_' . __FUNCTION__);
            $this->_importBatch = $batch;
        } else {
            $batch = $this->_importBatch;
        }
        $batch->setProgressTemplate($this->_('Remaining time: {remaining} - {msg}'));

        return parent::getImportOnlyBatch($batch);
    }
}
