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
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: DeleteRespondentSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Respondent;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 28-apr-2015 10:28:02
 */
class DeleteRespondentSnippet extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Optional: not always filled, use repeater
     *
     * @var array
     */
    protected $respondentData;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show';

    /**
     * Marker that the snippet is in undelete mode (for subclasses)
     *
     * @var boolean
     */
    protected $unDelete = false;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof \Gems_Model_RespondentModel) {
            $model = $this->model;

        } else {
            $model = $this->loader->getModels()->createRespondentModel();;
            $model->applyDeleteSettings();
        }

        return $model;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->unDelete) {
            return $this->_('Undelete respondent');
        }
        return $this->_('Delete or stop respondent');
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $this->_items = array(
                'gr2o_patient_nr',
                'gr2o_id_organization',
                'gr2o_id_user',
                'gr2o_reception_code',
                );

            if ($this->unDelete) {
                $this->_items[] = 'restore_tracks';
            }
        }
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if ($this->respondentData && (! $this->request->isPost())) {
            $this->formData = $this->respondentData;
        } else {
            parent::loadFormData();
        }

        $model = $this->getModel();

        if (array_key_exists('grc_success', $this->respondentData) && (! $this->respondentData['grc_success'])) {
            $this->unDelete = true;

            $options = $this->util->getReceptionCodeLibrary()->getRespondentRestoreCodes();
            $model->set('gr2o_reception_code', 'label', $this->_('Restore code'),
                    'multiOptions', $options,
                    'size', min(7, max(3, count($options) + 1))
                    );

            $model->set('restore_tracks', 'label', $this->_('Restore tracks'),
                    'description', $this->_('Restores tracks with the same code as the respondent.'),
                    'elementClass', 'Checkbox'
                    );

            if (! array_key_exists('restore_tracks', $this->formData)) {
                $this->formData['restore_tracks'] = 1;
            }
        } else {
            $options = $model->get('gr2o_reception_code', 'multiOptions');
        }

        if (! isset($this->formData['gr2o_reception_code'], $options[$this->formData['gr2o_reception_code']])) {
            reset($options);
            $this->formData['gr2o_reception_code'] = key($options);
        }

        if ($this->unDelete) {
            $this->saveLabel = $this->_('Restore respondent');
        } else {
            $this->saveLabel = $this->_('Delete respondent');
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        $model = $this->getModel();

        if (! $model instanceof \Gems_Model_RespondentModel) {
            parent::saveData();
        }

        $this->beforeSave();

        $oldCode = $this->respondentData['gr2o_reception_code'];
        $code    = $model->setReceptionCode(
                $this->formData['gr2o_patient_nr'],
                $this->formData['gr2o_id_organization'],
                $this->formData['gr2o_reception_code'],
                $this->formData['gr2o_id_user'],
                $oldCode
                );

        // Is the respondent really removed
        if ($code->isSuccess()) {
            $this->addMessage($this->_('Respondent restored.'));

            if ($this->formData['restore_tracks']) {
                $count     = 0;
                $respTrack = $this->loader->getTracker()->getRespondentTracks(
                        $this->formData['gr2o_id_user'],
                        $this->formData['gr2o_id_organization']
                        );
                $userId    = $this->loader->getCurrentUser()->getUserId();

                foreach ($respTracks as $respTrack) {
                    if ($respTrack instanceof \Gems_Tracker_RespondentTrack) {
                        if ($oldCode == $respTrack->getReceptionCode()->getCode()) {
                            $respTrack->setReceptionCode($code, null, $userId);
                            $count++;
                        }
                    }
                }
                $this->addMessage(sprintf($this->plural('Restored %d track.', 'Restored %d tracks.', $count), $count));
            }

        } else {
            // Perform actual save, but not simple stop codes.
            if ($code->isForRespondents()) {
                $this->addMessage($this->_('Respondent deleted.'));
                $this->afterSaveRouteKeys = false;
                $this->resetRoute         = true;
                $this->routeAction        = 'index';
            } else {
                // Just a stop code
                $this->addMessage($this->_('Respondent tracks stopped.'));
            }
        }

        $this->accesslog->logChange($this->request, null, $this->formData);

        // No after3Save() as we placed the messages here.
    }
}
