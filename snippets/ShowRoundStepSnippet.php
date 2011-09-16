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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowRoundStepSnippet extends Gems_Tracker_Snippets_ShowRoundSnippetAbstract
{
    /**
     *
     * @var array
     */
    private $_roundData;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var boolean True when only tracked fields should be retrieved by the nodel
     */
    protected $trackUsage = false;

    private function _addIf(array $names, MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        foreach ($names as $name) {
            if ($model->has($name, 'label')) {
                $bridge->addItem($name);
            }
        }
    }
    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->_roundData = $model->loadFirst();

        if ($this->trackEngine instanceof Gems_Tracker_Engine_StepEngineAbstract) {

            $this->trackEngine->updateRoundModelToItem($model, $this->_roundData, $this->locale->getLanguage());
        }

        $bridge->addItem('gro_id_track');
        $bridge->addItem('gro_id_survey');
        $bridge->addItem('gro_round_description');
        $bridge->addItem('gro_id_order');

        $bridge->addItem($model->get('valid_after', 'value'));
        $this->_addIf(array('grp_valid_after_source', 'grp_valid_after_id', 'grp_valid_after_field'), $bridge, $model);
        if ($model->has('grp_valid_after_length', 'label')) {
            $bridge->addItem(array($bridge->grp_valid_after_length, ' ', $bridge->grp_valid_after_unit), $model->get('grp_valid_after_length', 'label'));
        }

        $bridge->addItem($model->get('valid_for', 'value'));
        $this->_addIf(array('grp_valid_for_source', 'grp_valid_for_id', 'grp_valid_for_field'), $bridge, $model);
        if ($model->has('grp_valid_for_length', 'label')) {
            $bridge->addItem(array($bridge->grp_valid_for_length, ' ', $bridge->grp_valid_for_unit), $model->get('grp_valid_after_length', 'label'));
        }

        $bridge->addItem('gro_active');
        $bridge->addItem('gro_changed_event');
    }

    /**
     * Function that allows for overruling the repeater loading.
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return MUtil_Lazy_RepeatableInterface
     */
    public function getRepeater(MUtil_Model_ModelAbstract $model)
    {
        return new MUtil_Lazy_Repeatable(array($this->_roundData));
    }
}
