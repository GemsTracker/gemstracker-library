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
 * @version    $Id: MergeTrackSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Snippets;

use Gems\Tracker\Field\FieldInterface;
use Gems\Tracker\Round;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 15, 2016 3:57:15 PM
 */
class MergeTrackSnippetAbstract extends ImportMergeSnippetAbstract
{
    /**
     * Deactivate this current round
     */
    const ROUND_DEACTIVATE = -1;

    /**
     * Leave the current round as is
     */
    const ROUND_LEAVE      = -2;

    /**
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Add the settings from the transformed import data to the formData and the model
     *
     * @param \ArrayObject $import
     * @param \MUtil_Model_ModelAbstract $model
     */
    public function addExistingRoundsToModel(\ArrayObject $import, \MUtil_Model_ModelAbstract $model)
    {
        $currentRounds = $this->trackEngine->getRounds();

        if (! $currentRounds) {
            return;
        }

        $importRounds    = array();
        $newImportRounds = array();
        $tracker         = $this->loader->getTracker();

        foreach ($import['rounds'] as $lineNr => $roundData) {
            if (isset($roundData['survey_export_code'], $import['surveyCodes'][$roundData['survey_export_code']])) {
                $roundData['gro_id_survey'] = $import['surveyCodes'][$roundData['survey_export_code']];
                $round = $tracker->createTrackClass('Round', $roundData);

                $importRounds[$round->getRoundOrder()] = $round->getFullDescription();
                $newImportRounds[$round->getRoundOrder()] = sprintf(
                        $this->_('Set round to round %s'),
                        $round->getFullDescription()
                        );

                $import['roundOrderToLine'][$round->getRoundOrder()] = $lineNr;
            }
        }

        // Filter for rounds not in current track
        foreach ($currentRounds as $roundId => $round) {
            if ($round instanceof Round) {
                $order = $round->getRoundOrder();

                if (isset($newImportRounds[$order])) {
                    unset($newImportRounds[$order]);
                }
            }
        }

        $notEqualTo = array();  // Make sure no round is imported twice
        foreach ($currentRounds as $roundId => $round) {

            if ($round instanceof Round) {
                $name  = "round_$roundId";
                $order = $round->getRoundOrder();

                $model->set($name,
                        'existingRound', true,
                        'required', true,
                        'roundId', $roundId
                        );
                if (isset($importRounds[$order])) {
                    if ($round->getFullDescription() == $importRounds[$order]) {
                        $options = array(
                            self::ROUND_LEAVE => $this->_('Leave current round'),
                            $order => $this->_('Replace with import round'),
                            );
                    } else {
                        $options = array(
                            self::ROUND_LEAVE => $this->_('Leave current round'),
                            $order => sprintf(
                                    $this->_('Replace with import round %s'),
                                    $importRounds[$order]
                                    ),
                            );
                    }
                    $model->set($name,
                            'label', sprintf(
                                    $this->_('Matching round %s'),
                                    $round->getFullDescription()
                                    ),
                            'elementClass', 'Radio',
                            'multiOptions', $options
                            );
                    $value = $order;
                } else {
                    $model->set($name,
                            'label', sprintf(
                                    $this->_('Round not in import %s'),
                                    $round->getFullDescription()
                                    ),
                            'elementClass', 'Select',
                            'multiOptions', array(
                                self::ROUND_LEAVE => sprintf($this->_('Leave current round %d unchanged'), $order),
                                self::ROUND_DEACTIVATE => sprintf($this->_('Deactivate current round %d'), $order),
                                ) + $newImportRounds,
                            'size', 3 + count($newImportRounds)
                            );
                    $value = null;

                    if ($notEqualTo) {
                        $model->set($name, 'validators[notequal]', new \MUtil_Validate_NotEqualTo($notEqualTo));
                    }
                    $notEqualTo[] = $name;
                }
                if (! array_key_exists($name, $this->formData)) {
                    $this->formData[$name] = $value;
                }
            }
        }
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
                $this->addStepRoundMatch($bridge, $model);
                break;

            case 5:
                $this->addStepMergeTrack($bridge, $model);
                break;

            default:
                $this->addStepFileImport($bridge, $model);
                break;

        }
    }

    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepMergeTrack(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Merging the tracks.'), 'h3');

        $batch = $this->getImportMergeBatch();
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
                $element->pInfo($this->_('Merge errors occurred!'));
            } else {
                $element->h2($this->_('Tracks mergeded successfully!'));
                $element->pInfo($this->_('Click the "Finish" button to see the merged track.'));
            }
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);
    }

    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepRoundMatch(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Match the current track rounds to import rounds.'), 'h3');

        // Load the import data form settings
        $import = $this->loadImportData();

        $this->addExistingRoundsToModel($import, $model);

        $rounds = $model->getColNames('existingRound');

        if ($rounds) {
            $this->addItems($bridge, $rounds);
        } else {
            $bridge->addHtml('existingRound')->pInfo($this->_('No rounds in current track.'));
        }
    }

    /**
     * Overrule this function for any activities you want to take place
     * after the form has successfully been validated, but before any
     * buttons are processed.
     *
     * @param int $step The current step
     */
    protected function afterFormValidationFor($step)
    {
        parent::afterFormValidationFor($step);

        if (4 == $step) {
            $import = $this->loadImportData();
            $model  = $this->getModel();
            $saves  = array();

            $import['deactivateRounds'] = array();

            foreach ($model->getCol('roundId') as $name => $roundId) {
                $round = $this->trackEngine->getRound($roundId);
                if (isset($this->formData[$name]) && $this->formData[$name] && $round instanceof Round) {
                    switch ($this->formData[$name]) {
                        case self::ROUND_DEACTIVATE:
                            $import['deactivateRounds'][$roundId] = $round->getFullDescription();
                            break;

                        case self::ROUND_LEAVE:
                            if (isset($import['roundOrderToLine'][$round->getRoundOrder()])) {
                                $lineNr = $import['roundOrderToLine'][$round->getRoundOrder()];
                                unset($import['rounds'][$lineNr]);
                            }
                            $import['roundOrders'][$round->getRoundOrder()] = $roundId;
                            break;

                        default:
                            if (isset($import['roundOrderToLine'][$this->formData[$name]])) {
                                $lineNr = $import['roundOrderToLine'][$this->formData[$name]];
                                $import['rounds'][$lineNr]['gro_id_round'] = $roundId;
                            }
                            $import['roundOrders'][$this->formData[$name]] = $roundId;
                            break;
                    }
                }
            }
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
        $this->addMessage($this->_('Track merge finished'));
    }

    /**
     * Creates from the model a \Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @param int $step The current step
     * @return \Zend_Form
     */
    protected function getFormFor($step)
    {
        $baseform = $this->createForm();
        if (($step == 4) && (\MUtil_Bootstrap::enabled() !== true) && $baseform instanceof \MUtil_Form) {
            $model = $this->getModel();
            $table = new \MUtil_Html_DivFormElement();
            $table->setAsFormLayout($baseform);

            $baseform->setAttrib('class', $this->class);

            $bridge = $model->getBridgeFor('form', $baseform);

            $this->_items = null;
            $this->initItems();

            $this->addFormElementsFor($bridge, $model, $step);

            return $baseform;
        } else {
            return parent::getFormFor($step);
        }
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
                $this->_('Merge import into "%s" track. Step %d of %d.'),
                $this->trackEngine->getTrackName(),
                $step,
                $this->getStepCount()
                );
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportCheckBatch()
    {
        $batch = parent::getImportCheckBatch();

        $batch->setVariable('trackEngine', $this->trackEngine);

        return $batch;
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportMergeBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_create_' . $this->formData['import_id']);
        $import = $this->loadImportData();

        $batch->setVariable('import', $import);
        $batch->setVariable('trackEngine', $this->trackEngine);

        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            $batch->addTask(
                    'Tracker\\Merge\\MergeTrackImportTask',
                    $this->formData
                    );

            $fieldDef = $this->trackEngine->getFieldsDefinition();
            foreach ($import['fields'] as $lineNr => &$fieldData) {
                $field = $fieldDef->getFieldByOrder($fieldData['gtf_id_order']);
                if ($field instanceof FieldInterface) {
                    $fieldData['gtf_id_field'] = $field->getFieldId();
                }

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

            if (isset($import['deactivateRounds'])) {
                foreach ($import['deactivateRounds'] as $roundId => $roundDescription) {
                    $batch->addTask(
                            'Tracker\\Merge\\DeactivateTrackFieldTask',
                            $roundId,
                            $roundDescription
                            );
                }
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
        return 5;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        $this->formData['gtr_id_track'] = $this->trackEngine->getTrackId();
    }
}
