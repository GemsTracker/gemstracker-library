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
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditTrackTokenSnippet extends \Gems_Tracker_Snippets_EditTokenSnippetAbstract
{
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
        $onOffFields = array('gr2t_track_info', 'gto_round_description', 'grc_description');
        foreach ($onOffFields  as $field) {
            if (! (isset($this->formData[$field]) && $this->formData[$field])) {
                $model->set($field, 'elementClass', 'None');
            }
        }

        parent::addFormElements($bridge, $model);
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('track', 'show', $this->_('Show token'))
                ->addCurrentParent($this->_('Show track'))
                // ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $this->_items = array_merge(
                    array(
                        'gto_id_respondent',
                        'gr2o_patient_nr',
                        'respondent_name',
                        'gto_id_organization',
                        'gtr_track_name',
                        'gr2t_track_info',
                        'gto_round_description',
                        'gsu_survey_name',
                        'ggp_name',
                        'gto_valid_from_manual',
                        'gto_valid_from',
                        'gto_valid_until_manual',
                        'gto_valid_until',
                        'gto_comment',
                        'gto_mail_sent_date',
                        'gto_completion_time',
                        'grc_description',
                        'gto_changed',
                        'assigned_by',
                        ),
                    $this->getModel()->getMeta(\MUtil_Model_Type_ChangeTracker::HIDDEN_FIELDS, array())
                    );
            if (! $this->createData) {
                array_unshift($this->_items, 'gto_id_token');
            }
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    public function saveData()
    {
        $model = $this->getModel();

        if ($this->formData['gto_valid_until']) {
            // Make sure date based units are valid until the end of the day.
            $date = new \MUtil_Date(
                    $this->formData['gto_valid_until'],
                    $model->get('gto_valid_until', 'dateFormat')
                    );
            $date->setTimeToDayEnd();
            $this->formData['gto_valid_until'] = $date;
        }

        // Save the token using the model
        parent::saveData();
        // $this->token->setValidFrom($this->formData['gto_valid_from'], $this->formData['gto_valid_until'], $this->loader->getCurrentUser()->getUserId());

        // \MUtil_Echo::track($this->formData);

        // Refresh (NOT UPDATE!) token with current form data
        $updateData['gto_valid_from']         = $this->formData['gto_valid_from'];
        $updateData['gto_valid_from_manual']  = $this->formData['gto_valid_from_manual'];
        $updateData['gto_valid_until']        = $this->formData['gto_valid_until'];
        $updateData['gto_valid_until_manual'] = $this->formData['gto_valid_until_manual'];
        $updateData['gto_comment']            = $this->formData['gto_comment'];

        $this->token->refresh($updateData);

        $respTrack = $this->token->getRespondentTrack();
        $userId    = $this->loader->getCurrentUser()->getUserId();
        $changed   = $respTrack->checkTrackTokens($userId, $this->token);

        if ($changed) {
            $this->addMessage(sprintf($this->plural(
                    '%d token changed by recalculation.',
                    '%d tokens changed by recalculation.',
                    $changed
                    ), $changed));
        }
    }
}
