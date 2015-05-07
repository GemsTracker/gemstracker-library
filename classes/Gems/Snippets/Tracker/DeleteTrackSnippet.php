<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Tracker;

use Gems\Snippets\ReceptionCode\ChangeReceptionCodeSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class DeleteTrackSnippet extends ChangeReceptionCodeSnippetAbstract
{
    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $editItems = array('gr2t_comment');

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $exhibitItems = array(
        'gr2o_patient_nr', 'respondent_name', 'gtr_track_name', 'gr2t_track_info', 'gr2t_start_date',
        );

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected $hiddenItems = array('gr2t_id_respondent_track', 'gr2t_id_user', 'gr2t_id_organization');

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
    protected $receptionCodeItem = 'gr2t_reception_code';

    /**
     * Required
     *
     * @var \Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show-track';

    /**
     * Optional
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

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
        if (! $this->model instanceof \Gems_Tracker_Model_TrackModel) {
            $tracker     = $this->loader->getTracker();
            $this->model = $tracker->getRespondentTrackModel();

            if (! $this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
                $this->trackEngine = $this->respondentTrack->getTrackEngine();
            }
            $this->model->applyEditSettings($this->trackEngine);
        }

        $this->model->set('restore_tokens', 'label', $this->_('Restore tokens'),
                'description', $this->_('Restores tokens with the same code as the track.'),
                'elementClass', 'Checkbox'
                );

        return $this->model;
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'edit-track', $this->_('Edit track'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    }

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array
     */
    public function getReceptionCodes()
    {
        $rcLib = $this->util->getReceptionCodeLibrary();

        if ($this->unDelete) {
            return $rcLib->getTrackRestoreCodes();
        }

        return $rcLib->getTrackDeletionCodes();
    }

    /**
     * Called after loadFormData() in loadForm() before the form is created
     *
     * @return boolean Are we undeleting or deleting?
     */
    public function isUndeleting()
    {
        if ($this->respondentTrack->hasSuccesCode()) {
            return false;
        }

        $this->editItems[] = 'restore_tokens';
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
        $oldCode = $this->respondentTrack->getReceptionCode()->getCode();

        // Use the repesondent track function as that cascades the consent code
        $changed = $this->respondentTrack->setReceptionCode($newCode, $this->formData['gr2t_comment'], $userId);

        if ($this->unDelete) {
            $this->addMessage($this->_('Track restored.'));

            if (isset($this->formData['restore_tokens']) && $this->formData['restore_tokens']) {
                $count = 0;
                foreach ($this->respondentTrack->getTokens() as $token) {
                    if ($token instanceof \Gems_Tracker_Token) {
                        if ($oldCode === $token->getReceptionCode()->getCode()) {
                            $count += $token->setReceptionCode($newCode, null, $userId);
                        }
                    }
                }
                $this->addMessage(sprintf($this->plural(
                        '%d token reception codes restored.',
                        '%d tokens reception codes restored.',
                        $count
                        ), $count));
            }
        } else {
            $this->addMessage($this->_('Track deleted.'));
        }

        return $changed;
    }
}
