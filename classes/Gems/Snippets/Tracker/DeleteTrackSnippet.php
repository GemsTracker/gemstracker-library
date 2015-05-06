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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class DeleteTrackSnippet extends \Gems_Tracker_Snippets_EditTrackSnippetAbstract
{
    /**
     * Marker that the snippet is in undelete mode (for subclasses)
     *
     * @var boolean
     */
    protected $unDelete = false;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $bridge->addHidden(   'gr2t_id_respondent_track');
        $bridge->addHidden(   'gr2t_id_user');
        $bridge->addHidden(   'gr2t_id_track');
        $bridge->addHidden(   'gr2t_id_organization');
        $bridge->addHidden(   'gr2t_active');
        $bridge->addHidden(   'gr2t_count');
        $bridge->addHidden(   'gr2o_id_organization');
        $bridge->addHidden(   'gtr_id_track');
        $bridge->addHidden(   'grc_success');

        // Patient
        $bridge->addExhibitor('gr2o_patient_nr', 'label', $this->_('Respondent number'));
        $bridge->addExhibitor('respondent_name', 'label', $this->_('Respondent name'));

        // Track
        $bridge->addExhibitor('gtr_track_name');
        $bridge->addExhibitor('gr2t_track_info');
        $bridge->addExhibitor('gr2t_start_date');

        // The edit element
        $bridge->addList('gr2t_reception_code');

        if ($this->unDelete) {
            $bridge->addCheckbox('restore_tokens');
        }

        // Comment text
        $bridge->addTextarea('gr2t_comment', 'rows', 3, 'cols', 50);

        // Change the button
        $this->saveLabel = $this->getTitle();
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();

        if ($this->respondentTrack->hasSuccesCode()) {
            $options = $this->util->getReceptionCodeLibrary()->getTrackDeletionCodes();
        } else {
            $this->unDelete = true;
            $options = $this->util->getReceptionCodeLibrary()->getTrackRestoreCodes();
        }

        $model->set('gr2t_reception_code',
            'label',        ($this->unDelete ? $this->_('Reception code') : $this->_('Rejection code')),
            'multiOptions', $options,
            'required',     true,
            'size',         min(7, max(3, count($options) + 1)));

        if ($this->unDelete) {
            $model->set('restore_tokens', 'label', $this->_('Restore tokens'),
                    'description', $this->_('Restores tokens with the same code as the track.'),
                    'elementClass', 'Checkbox'
                    );
        }

        return $model;
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
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        if ($this->unDelete) {
            return sprintf($this->_('Undelete %s!'), $this->getTopic());
        }
        return sprintf($this->_('Delete %s!'), $this->getTopic());
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
        $oldCode = $this->respondentTrack->getReceptionCode()->getCode();
        $userId  = $this->loader->getCurrentUser()->getUserId();
        $newCode = $this->formData['gr2t_reception_code'];

        // Use the repesondent track function as that cascades the consent code
        $changed = $this->respondentTrack->setReceptionCode($newCode, $this->formData['gr2t_comment'], $userId);

        if ($this->unDelete && isset($this->formData['restore_tokens']) && $this->formData['restore_tokens']) {
            foreach ($this->respondentTrack->getTokens() as $token) {
                if ($token instanceof \Gems_Tracker_Token) {
                    if ($oldCode === $token->getReceptionCode()->getCode()) {
                        $token->setReceptionCode($newCode, null, $userId);
                    }
                }
            }
        }

        // Tell the user what happened
        $this->afterSave($changed);
    }
}
