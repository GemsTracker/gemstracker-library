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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Organization_OrganizationTableSnippet extends Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = 'gor_name';

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->tr()->class = $bridge->row_class;

        if ($showMenuItem = $this->getShowMenuItem()) {
            $bridge->addItemLink($showMenuItem->toActionLinkLower($this->request, $bridge));
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        $HTML = MUtil_Html::create();
        $BR = $HTML->br();

        $orgName[] = MUtil_Lazy::iff($bridge->gor_url,
                MUtil_Html_AElement::a($bridge->gor_name, array('href' => $bridge->gor_url, 'target' => '_blank', 'class' => 'globe')),
                $bridge->gor_name);
        $orgName[] = $bridge->createSortLink('gor_name');

        $mailName[] = MUtil_Lazy::iff($bridge->gor_contact_email,
                MUtil_Html_AElement::email(MUtil_Lazy::first($bridge->gor_contact_name, $bridge->gor_contact_email), array('href' => array('mailto:', $bridge->gor_contact_email))),
                $bridge->gor_contact_name);
        $mailName[] = $bridge->createSortLink('gor_contact_name');

        $bridge->addMultiSort($orgName, $BR, 'gor_task', $BR, 'gor_location');
        $bridge->addMultiSort($mailName, $BR, 'gor_style', $BR, 'gor_iso_lang');
        $bridge->addMultiSort('gor_active', $BR, 'gor_add_respondents', $BR, 'gor_has_respondents');
        // $bridge->add('gor_accessible_by');

        if ($editMenuItem = $this->getEditMenuItem()) {
            $bridge->addItemLink($editMenuItem->toActionLinkLower($this->request, $bridge));
        }
    }
}
