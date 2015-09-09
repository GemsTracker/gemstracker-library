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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Mail\Log;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class MailLogBrowseSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->getShowMenuItems();

            foreach ($showMenuItems as $menuItem) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->request, $bridge));
            }
        }

        // Newline placeholder
        $br = \MUtil_Html::create('br');
        $by = \MUtil_Html::raw($this->_(' / '));
        $sp = \MUtil_Html::raw('&nbsp;');

        // make sure search results are highlighted
        $this->applyTextMarker();

        $bridge->addMultiSort('grco_created',  $br, 'gr2o_patient_nr', $sp, 'respondent_name', $br, 'grco_address', $br, 'gtr_track_name');
        $bridge->addMultiSort('grco_id_token', $br, 'assigned_by',     $br, 'grco_sender',     $br, 'gsu_survey_name');
        $bridge->addMultiSort('status',        $by, 'filler',          $br, 'grco_topic');

        if ($this->showMenu) {
            $items  = $this->findMenuItems('track', 'show');
            $links  = array();
            $params = array('gto_id_token'  => $bridge->gto_id_token, \Gems_Model::ID_TYPE => 'token');
            $title  = \MUtil_Html::create('strong', $this->_('+'));


            foreach ($items as $item) {
                if ($item instanceof \Gems_Menu_SubMenuItem) {
                    $bridge->addItemLink($item->toActionLinkLower($this->request, $params, $title));
                }
            }
        }
        $bridge->getTable()->appendAttrib('class', 'compliance');

        $tbody = $bridge->tbody();
        $td = $tbody[0][0];
        $td->appendAttrib(
                'class',
                \MUtil_Lazy::method($this->util->getTokenData(), 'getStatusClass', $bridge->getLazy('status'))
                );
    }
}
