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

use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 28-apr-2015 10:28:02
 */
class DeleteRespondentSnippet extends ChangeReceptionCodeSnippetAbstract
{
    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $editItems = array();

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $exhibitItems = array('gr2o_patient_nr', 'gr2o_id_organization');

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected $hiddenItems = array('grs_id_user');

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected $receptionCodeItem = 'gr2o_reception_code';

    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

    /**
     * Optional right to check for undeleting
     *
     * @var string
     */
    protected $unDeleteRight = 'pr.respondent.undelete';

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // Do nothing, performed in setReceptionCode()
    }

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
            if ($this->respondent instanceof \Gems_Tracker_Respondent) {
                $model = $this->respondent->getRespondentModel();

            } else {
                $model = $this->loader->getModels()->getRespondentModel(true);;
            }
            $model->applyDetailSettings();
        }

        return $model;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array code name => description
     */
    public function getReceptionCodes()
    {
        $rcLib = $this->util->getReceptionCodeLibrary();

        if ($this->unDelete) {
            return $rcLib->getRespondentRestoreCodes();
        }
        return $rcLib->getRespondentDeletionCodes();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if (! $this->request->isPost()) {
            if ($this->respondent instanceof \Gems_Tracker_Respondent) {
                $this->formData = $this->respondent->getArrayCopy();
            }
        }

        if (! $this->formData) {
            parent::loadFormData();
        }

        $model = $this->getModel();

        $model->set('restore_tracks', 'label', $this->_('Restore tracks'),
                'description', $this->_('Restores tracks with the same code as the respondent.'),
                'elementClass', 'Checkbox'
                );

        if (! array_key_exists('restore_tracks', $this->formData)) {
            $this->formData['restore_tracks'] = 1;
        }
    }

    /**
     * Are we undeleting or deleting?
     *
     * @return boolean
     */
    public function isUndeleting()
    {
        if ($this->respondent->getReceptionCode()->isSuccess()) {
            return false;
        }

        $this->editItems[] = 'restore_tracks';
        return true;
    }

    /**
     * Hook performing actual save
     *
     * @param string $newCode
     * @param int $userId
     * @return $changed
     */
    public function setReceptionCode($newCode, $userId)
    {
        $model   = $this->getModel();
        $oldCode = $this->respondent->getReceptionCode()->getCode();
        $code    = $model->setReceptionCode(
                $this->formData['gr2o_patient_nr'],
                $this->formData['gr2o_id_organization'],
                $newCode,
                $userId,
                $oldCode
                );

        // Is the respondent really removed
        if ($code->isSuccess()) {
            $this->addMessage($this->_('Respondent restored.'));

            if ($this->formData['restore_tracks']) {
                $count      = 0;
                $respTracks = $this->loader->getTracker()->getRespondentTracks(
                        $this->formData['grs_id_user'],
                        $this->formData['gr2o_id_organization']
                        );

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
                $this->addMessage(sprintf($this->plural('Stopped %d track.', 'Stopped %d tracks.', $count), $count));
            }
        }

        return 1;
    }
}
