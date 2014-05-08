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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * The maintenace screen for the action log
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_LogMaintenanceAction extends Gems_Controller_BrowseEditAction {

    public $sortKey = array('glac_name' => SORT_ASC);

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    public function addFormElements(MUtil_Model_Bridge_FormBridgeInterface $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false) {
        $model->set('glac_name', 'elementClass', 'exhibitor');
        $model->set('glac_log', 'elementClass', 'checkBox');
        parent::addFormElements($bridge, $model, $data, $new);
    }

    protected function createModel($detailed, $action) {
        //MUtil_Model::$verbose=true;
        $model = new Gems_Model_JoinModel('log_maint', 'gems__log_actions', true);
        $model->set('glac_name', 'label', $this->_('Action'));
        $model->set('glac_log', 'label', $this->_('Log'), 'multiOptions', $this->util->getTranslated()->getYesNo());

        return $model;
    }

    public function afterSave(array $data, $isNew)
    {
        $this->loader->getUtil()->getAccessLogActions()->invalidateCache();
        return parent::afterSave($data, $isNew);
    }

    public function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data) {
        $elements = parent::getAutoSearchElements($model, $data);

        if ($elements) {
            $elements[] = null; // break into separate spans
        }

        $elements[] = $this->_('Log:');
        $elements[] = $this->_createSelectElement('glac_log', $this->util->getTranslated()->getYesNo(), $this->_('All'));

        return $elements;
    }

    public function getTopic($count = 1) {
        return $this->_('Log action');
    }

    public function getTopicTitle() {
        return $this->_('Log maintenance');
    }

}