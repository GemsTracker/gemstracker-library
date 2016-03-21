<?php

/**
 * Copyright (c) 2016, Erasmus MC
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
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 16, 2016 4:54:15 PM
 */
class RespondentTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
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
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn1(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');

        if (isset($this->searchFilter['grc_success']) && (! $this->searchFilter['grc_success'])) {
            $model->set('grc_description', 'label', $this->_('Rejection code'));
            $column2 = 'grc_description';

        } elseif (isset($this->searchFilter[\MUtil_Model::REQUEST_ID2])) {
            $model->setIfExists('gr2o_opened', 'tableDisplay', 'small');
            $column2 = 'gr2o_opened';

        } else {
            $column2 = 'gr2o_id_organization';
        }

        $bridge->addMultiSort('gr2o_patient_nr', $br, $column2);
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn2(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');

        $model->setIfExists('grs_email', 'formatFunction', array('MUtil_Html_AElement', 'ifmail'));

        $bridge->addMultiSort('name', $br, 'grs_email');
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn3(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');

        if ($model->hasAlias('gems__respondent2track')) {
            $model->set('gtr_track_name',  'label', $this->_('Track'));
            $model->set('gr2t_track_info', 'label', $this->_('Track description'));

            $items = $this->findMenuItems('track', 'show-track');
            $track = 'gtr_track_name';
            if ($items) {
                $menuItem = reset($items);
                if ($menuItem instanceof \Gems_Menu_MenuAbstract) {
                    $href  = $menuItem->toHRefAttribute(
                            $this->request,
                            $bridge,
                            array('gr2t_id_respondent_track' => $bridge->gr2t_id_respondent_track)
                            );
                    $track = array();
                    $track[0] = \MUtil_Lazy::iif($bridge->gr2t_id_respondent_track,
                            \MUtil_Html::create()->a(
                                    $href,
                                    $bridge->gtr_track_name,
                                    array('onclick' => "event.cancelBubble = true;")
                                    )
                            );
                    $track[1] = $bridge->createSortLink('gtr_track_name');
                }
            }

            $bridge->addMultiSort($track, $br, 'gr2t_track_info');
        } else {
            $citysep  = \MUtil_Html::raw('&nbsp;&nbsp;'); // $bridge->itemIf($bridge->grs_zipcode, \MUtil_Html::raw('&nbsp;&nbsp;'));

            $bridge->addMultiSort('grs_address_1', $br, 'grs_zipcode', $citysep, 'grs_city');
        }
    }

    /**
     * Add first columns (group) from the model to the bridge that creates the browse table.
     *
     * You can actually add more than one column in this function, but just call all four functions
     * with the default columns in each
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseColumn4(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');

        // Display separator and phone sign only if phone exist.
        $phonesep = \MUtil_Html::raw('&#9743; '); // $bridge->itemIf($bridge->grs_phone_1, \MUtil_Html::raw('&#9743; '));

        $bridge->addMultiSort('grs_birthday', $br, $phonesep, 'grs_phone_1');
    }

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
        if ($this->columns) {
            parent::addBrowseTableColumns($bridge, $model);
            return;
        }
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->getShowMenuItems();

            foreach ($showMenuItems as $menuItem) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->request, $bridge));
            }
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        $this->addBrowseColumn1($bridge, $model);
        $this->addBrowseColumn2($bridge, $model);
        $this->addBrowseColumn3($bridge, $model);
        $this->addBrowseColumn4($bridge, $model);

        if ($this->showMenu) {
            $editMenuItems = $this->getEditMenuItems();

            foreach ($editMenuItems as $menuItem) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->request, $bridge));
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof \Gems_Model_RespondentModel) {
            $this->model = $this->loader->getModels()->createRespondentModel();
            $this->model->applyBrowseSettings();
        }
        return $this->model;
    }
}
