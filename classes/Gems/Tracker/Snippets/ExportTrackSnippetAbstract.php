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
 * @version    $Id: ExportTrackSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 4, 2016 11:20:07 AM
 */
class ExportTrackSnippetAbstract extends \MUtil_Snippets_WizardFormSnippetAbstract
{
    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $exportModel;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepDownloadExportFile(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // $this->addItems($bridge);
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
        $this->displayHeader($bridge, sprintf(
                $this->_('%s track export. Step %d of %d.'),
                $this->trackEngine->getTrackName(),
                $step,
                $this->getStepCount()), 'h1');

        switch ($step) {
            case 0:
            case 1:
                $this->addStepExportSettings($bridge, $model);
                break;

            case 2:
                $this->addStepExportCodes($bridge, $model);
                break;

            case 3:
                $this->addStepGenerateExportFile($bridge, $model);
                break;

            default:
                $this->addStepDownloadExportFile($bridge, $model);
                break;

        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepExportCodes(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Set the survey export codes'), 'h3');

        $rounds      = $this->formData['rounds'];
        $surveyCodes = array();
        // $validator   = $this->loader->getTracker()->createTrackClass($className, $surveyCodes, $rounds)

        foreach ($rounds as $roundId) {
           $round = $this->trackEngine->getRound($roundId);
           $name  = 'survey__' . $round->getSurveyId();

           $surveyCodes[$name] = $name;
        }

        $this->addItems($bridge, $surveyCodes);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepExportSettings(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Select what to export'), 'h3');

        $this->addItems($bridge, 'orgs', 'fields', 'rounds');
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepGenerateExportFile(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // $this->addItems($bridge);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->exportModel instanceof \MUtil_Model_ModelAbstract) {
            $yesNo = $this->util->getTranslated()->getYesNo();

            $model = new \MUtil_Model_SessionModel('export_for_' . $this->request->getControllerName());

            $model->set('orgs', 'label', $this->_('Organization export'),
                    'default', 1,
                    'description', $this->_('Export the organzations for which the track is active'),
                    'multiOptions', $yesNo,
                    'required', true,
                    'elementClass', 'Checkbox');

            $model->set('fields', 'label', $this->_('Field export'));
            $fields = $this->trackEngine->getFieldNames();
            if ($fields) {
                $model->set('fields',
                        'default', array_keys($fields),
                        'description', $this->_('Check the fields to export'),
                        'elementClass', 'MultiCheckbox',
                        'multiOptions', $fields
                        );
            } else {
                $model->set('fields',
                        'elementClass', 'Exhibitor',
                        'value', $this->_('No fields to export')
                        );
            }

            $rounds = $this->trackEngine->getRoundDescriptions();
            $model->set('rounds', 'label', $this->_('Round export'));
            if ($rounds) {
                $defaultRounds = array();
                foreach ($rounds as $roundId => &$roundDescription) {
                    $round = $this->trackEngine->getRound($roundId);
                    if ($round && $round->isActive()) {
                        $defaultRounds[] = $roundId;
                    } else {
                        $roundDescription = sprintf($this->_('%s (inactive)'), $roundDescription);
                    }

                    $survey = $round->getSurvey();
                    if ($survey) {
                        $model->set('survey__' . $survey->getSurveyId(),
                                'label', $survey->getName(),
                                'default', $survey->getExportCode(),
                                'description', $this->_('A unique code indentifying this survey during import'),
                                'required', true,
                                'size', 20,
                                'survey', true,
                                'maxlength', 64
                                );
                    }
                }
                $model->set('rounds',
                        'default', $defaultRounds,
                        'description', $this->_('Check the rounds to export'),
                        'elementClass', 'MultiCheckbox',
                        'multiOptions', $rounds
                        );
            } else {
                $model->set('rounds',
                        'elementClass', 'Exhibitor',
                        'value', $this->_('No rounds to export')
                        );
            }

            $this->exportModel = $model;
        }

        return $this->exportModel;
    }

    /**
     * Display a header
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param mixed $header Header content
     * @param string $tagName
     */
    protected function displayHeader(\MUtil_Model_Bridge_FormBridgeInterface $bridge, $header, $tagName = 'h2')
    {
        static $count = 0;

        $count += 1;
        \MUtil_Echo::track($count);
        $element = $bridge->getForm()->createElement('html', 'step_header_' . $count);
        $element->$tagName($header);

        $bridge->addElement($element);
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        $model = $this->getModel();

        if ($this->request->isPost()) {
            $this->formData = $model->loadPostData($this->request->getPost() + $this->formData, true);

        } else {
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData = $model->loadNew();
            }
        }
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
