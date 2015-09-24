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
 * @subpackage Snippets\Staff
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: StaffTableSnippet.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Snippets\Staff;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 24-sep-2015 16:23:26
 */
class StaffTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'staff';

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

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
        $this->columns = array(
            10 => array('gsf_login'),
            20 => array('name'),
            30 => array('gsf_email'),
            40 => array('gsf_id_primary_group'),
            50 => array('gsf_gender'),
        );
        if (count($this->loader->getCurrentUser()->getAllowedOrganizations()) > 1) {
            $br = \MUtil_Html::create('br');

            $this->columns[20] = array('name', $br, 'gsf_email');
            $this->columns[30] = array('gsf_id_organization');
        }
        parent::addBrowseTableColumns($bridge, $model);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof \Gems_Model_StaffModel) {
            $model = $this->model;
        } else {
            $model = $this->loader->getModels()->getStaffModel();
            $model->applyBrowseSettings();
        }

        return $model;
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItems()
    {
        $resets = $this->findMenuItems($this->menuActionController, 'reset');
        foreach ($resets as $resetPw) {
            if ($resetPw instanceof \Gems_Menu_SubMenuItem) {
                $resetPw->set('label', $this->_('password'));
            }
        }
        return array_merge(
                parent::getEditMenuItems(),
                $resets,
                $this->findMenuItems($this->menuActionController, 'mail')
                );
    }
}
