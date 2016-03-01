<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ImportTrackSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 15, 2016 3:57:15 PM
 */
class ImportTrackSnippetAbstract extends ImportMergeSnippetAbstract
{
    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepCreateTrack(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Creating the track.'), 'h3');

        $batch = $this->getImportCreateBatch();
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil_Registry_Source::$verbose = true;
        if ($batch->run($this->request)) {
            exit;
        }

        $element = $form->createElement('html', $batch->getId());

        if ($batch->isFinished()) {
            $this->nextDisabled = $batch->getCounter('create_errors');
            $batch->autoStart   = false;

            // Keep the filename after $batch->getMessages(true) cleared the previous
            $this->addMessage($batch->getMessages(true));
            if ($this->nextDisabled) {
                $element->pInfo($this->_('Create errors occurred!'));
            } else {
                $element->h2($this->_('Track created successfully!'));
                $element->pInfo($this->_('Click the "Finish" button to see the track.'));
            }
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);

        // \MUtil_Echo::track($this->loadImportData()->getArrayCopy());
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model, $step)
    {
        $this->displayHeader($bridge, $this->getFormTitle($step), 'h1');

        switch ($step) {
            case 2:
                $this->addStepFileCheck($bridge, $model);
                break;

            case 3:
                $this->addStepChangeTrack($bridge, $model);
                break;

            case 4:
                $this->addStepCreateTrack($bridge, $model);
                break;

            default:
                $this->addStepFileImport($bridge, $model);
                break;

        }
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        $this->addMessage($this->_('Track import finished'));
    }

    /**
     * Get the title at the top of the form
     *
     * @param int $step The current step
     * @return string
     */
    protected function getFormTitle($step)
    {
        return sprintf(
                $this->_('New track import. Step %d of %d.'),
                $step,
                $this->getStepCount()
                );
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportCreateBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_create_' . $this->formData['import_id']);
        $import = $this->loadImportData();

        $batch->setVariable('import', $import);

        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            $batch->addTask(
                    'Tracker\\Import\\CreateTrackImportTask',
                    $this->formData
                    );

            foreach ($import['fields'] as $lineNr => $fieldData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackFieldImportTask',
                        $lineNr,
                        $fieldData
                        );
            }

            foreach ($import['rounds'] as $lineNr => $roundData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackRoundImportTask',
                        $lineNr,
                        $roundData
                        );
            }

            $batch->addTask(
                    'AddTask',
                    'Tracker\\Import\\FinishTrackImport'
                    );
        }

        return $batch;
    }

    /**
     * The number of steps in this form
     *
     * @return int
     */
    protected function getStepCount()
    {
        return 4;
    }
}