<?php

/**
 *
 * @package    Gems
 * @subpackage Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Gems specific importer class
 *
 * @package    Gems
 * @subpackage Import
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Import_Importer extends \MUtil_Model_Importer
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @param string $idPart End part for batch id
     * @param \MUtil_Task_TaskBatch $batch Optional batch with different source etc..
     * @return \MUtil_Task_TaskBatch
     */
    protected function getBasicImportBatch($idPart, \MUtil_Task_TaskBatch $batch = null)
    {
        if (null === $batch) {
            $batch = $this->loader->getTaskRunnerBatch('check_' . basename($this->sourceModel->getName()) . '_' . $idPart);
        }
        return parent::getBasicImportBatch($idPart, $batch);
    }
    /**
     *
     * @param \MUtil_Task_TaskBatch $batch Optional batch with different source etc..
     * @return \MUtil_Task_TaskBatch
     */
    public function getImportOnlyBatch(\MUtil_Task_TaskBatch $batch = null)
    {
        if (! $this->_importBatch instanceof \MUtil_Task_TaskBatch) {
            $batch = $this->loader->getTaskRunnerBatch(__CLASS__ . '_import_' .
                    basename($this->sourceModel->getName()) . '_' . __FUNCTION__);

            $batch->setVariable('targetModel', $this->getTargetModel());

            $this->_importBatch = $batch;
        } else {
            $batch = $this->_importBatch;
        }

        return parent::getImportOnlyBatch($batch);
    }
}
