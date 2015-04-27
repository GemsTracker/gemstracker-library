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
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.\
 *
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

/**
 * Display snippet for standard track tokens
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowTrackTokenSnippet extends \Gems_Tracker_Snippets_ShowTokenSnippetAbstract
{
    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // \MUtil_Model::$verbose = true;

        // Extra item needed for menu items
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->grc_success;

        $controller = $this->request->getControllerName();
        $links      = $this->getMenuList();
        $links->addParameterSources($this->request, $bridge);

        $bridge->addItem('gto_id_token', null, array('colspan' => 1.5));

        $buttons = $links->getActionLinks(true, 'ask', 'take', 'pdf', 'show', $controller, 'questions', $controller, 'answer');
        if (count($buttons)) {
            $bridge->tr();
            $bridge->tdh($this->_('Actions'));
            $bridge->td($buttons, array('colspan' => 2, 'skiprowclass' => true));
        }
        $bridge->addMarkerRow();

        $bridge->add('gr2o_patient_nr');
        $bridge->add('respondent_name');
        $bridge->addMarkerRow();

        $bridge->add('gtr_track_name');
        $bridge->add('gr2t_track_info');
        $bridge->add('assigned_by');
        $bridge->addMarkerRow();

        $bridge->add('gsu_survey_name');
        $bridge->add('gto_round_description');
        $bridge->add('ggp_name');
        $bridge->addMarkerRow();

        // Editable part (INFO / VALID FROM / UNTIL / E-MAIL
        $button = $links->getActionLink($controller, 'edit', true);
        $bridge->addWithThird('gto_valid_from_manual', 'gto_valid_from', 'gto_valid_until_manual', 'gto_valid_until', 'gto_comment', $button);

        // E-MAIL
        $button = $links->getActionLink($controller, 'email', true);
        $bridge->addWithThird('gto_mail_sent_date', 'gto_mail_sent_num', $button);

        // COMPLETION DATE
        $fields = array();
        if ($this->token->getReceptionCode()->hasDescription()) {
            $bridge->addMarkerRow();
            $fields[] = 'grc_description';
        }
        $fields[] = 'gto_completion_time';
        if ($this->token->isCompleted()) {
            $fields[] = 'gto_duration_in_sec';
        }
        if ($this->token->hasResult()) {
            $fields[] = 'gto_result';
        }
        $fields[] = $links->getActionLink($controller, 'delete', true);

        $bridge->addWithThird($fields);

        if ($links->count()) {
            $bridge->tfrow($links, array('class' => 'centerAlign'));
        }

        foreach ($bridge->tbody() as $row) {
            if (isset($row[1]) && ($row[1] instanceof \MUtil_Html_HtmlElement)) {
                if (isset($row[1]->skiprowclass)) {
                    unset($row[1]->skiprowclass);
                } else {
                    $row[1]->appendAttrib('class', $bridge->row_class);
                }
            }
        }
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addCurrentParent($this->_('Show track'))
                ->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addCurrentSiblings()
                ->addCurrentChildren()
                ->showDisabled();

        // \MUtil_Echo::track($links->count());

        return $links;
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        return $this->_('Show token');
    }
}
