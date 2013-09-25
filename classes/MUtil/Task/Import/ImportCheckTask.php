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
 * @package    MUtil
 * @subpackage Task_Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ImportCheckTask.php$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Task_Import
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Task_Import_ImportCheckTask extends MUtil_Task_IteratorTaskAbstract
{
    /**
     * When false, the task is not added (for when just checking)
     *
     * @var boolean
     */
    protected $addImport = true;

    /**
     *
     * @var MUtil_Task_TaskBatch
     */
    protected $importBatch;

    /**
     * The number of import errors after which the check is aborted.
     *
     * @var int
     */
    protected $importErrorsAllowed = 10;

    /**
     *
     * @var MUtil_Model_ModelTranslatorInterface
     */
    protected $modelTranslator;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->modelTranslator instanceof MUtil_Model_ModelTranslatorInterface) &&
            parent::checkRegistryRequestsAnswers();
    }

    /**
     * Execute a single iteration of the task.
     *
     * @param scalar $key The current iterator key
     * @param mixed $current The current iterator content
     * @param array $params The parameters to the execute function
     */
    public function executeIteration($key, $current, array $params)
    {
        // MUtil_Echo::track($key, $current);
        // Ignore empty rows.
        if (! $current) {
            return;
        }

        $batch = $this->getBatch();

        $row = $this->modelTranslator->translateRowValues($current, $key);

        if ($row) {
            $row = $this->modelTranslator->validateRowValues($row, $key);
        }
        $batch->addToCounter('import_checked');

        $errorCount = $batch->getCounter('import_errors');
        $checked    = $batch->getCounter('import_checked');
        $checkMsg   = sprintf($this->plural('%d record checked', '%d records checked', $checked), $checked);

        $errors = $this->modelTranslator->getRowErrors($key);
        foreach ($errors as $error) {
            $batch->addToCounter('import_errors');
            $batch->addMessage($error);
        }

        // MUtil_Echo::track($key, $row, $errors);

        if (0 === $errorCount) {
            if ($row) {
                // Do not report empty rows
                if ($this->addImport && $this->importBatch) {
                    $this->importBatch->setTask('Import_SaveToModel', 'import-' . $key, $row);
                }
                $batch->setMessage('check_status', sprintf($this->_('%s, no problems found.'), $checkMsg));
            }
        } else {
            if ($errorCount >= $this->importErrorsAllowed) {
                $batch->stopBatch(sprintf(
                        $this->plural('%s, one import problem found. Import aborted.',
                                '%s, %d import problems found. Import aborted.',
                                $errorCount),
                        $checkMsg,
                        $errorCount));

            } else {
                $batch->setMessage('check_status', sprintf(
                        $this->plural('%s, one import problem found, continuing check.',
                                '%s, %d import problems found, continuing check.',
                                $errorCount),
                        $checkMsg,
                        $errorCount));
            }
        }
    }

    /**
     * Sets the batch this task belongs to
     *
     * This method will be called from the Gems_Task_TaskRunnerBatch upon execution of the
     * task. It allows the task to communicate with the batch queue.
     *
     * @param MUtil_Task_TaskBatch $batch
     * @return MUtil_Task_TaskInterface (continuation pattern)
     */
    public function setBatch(MUtil_Task_TaskBatch $batch)
    {
        parent::setBatch($batch);

        if (! $this->importBatch instanceof MUtil_Task_TaskBatch) {
            $this->importBatch = $batch;
        }

        return $this;
    }
}
