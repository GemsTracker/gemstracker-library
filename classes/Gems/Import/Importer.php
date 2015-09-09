<?php

/**
 * Copyright (c) 2013, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
