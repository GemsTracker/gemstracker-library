<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage AppointmentShowSnippet
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: AppointmentShowSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentShowSnippet
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Agenda_AppointmentShowSnippet extends Gems_Snippets_ModelItemTableSnippetAbstract
{
    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_VerticalTableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     * /
    protected function addShowTableRows(MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        parent::addShowTableRows($bridge, $model);
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof Gems_Model_AppointmentModel) {
            $this->model = $this->loader->getModels()->createAppointmentModel();
            $this->model->applyDetailSettings();
        }
        $this->model->set('gap_admission_time', 'formatFunction', array($this, 'displayDate'));
        $this->model->set('gap_discharge_time', 'formatFunction', array($this, 'displayDate'));

        return $this->model;
    }

    public function displayDate($date)
    {
        if (! $date instanceof Zend_Date) {
            return $date;
        }
        $div = MUtil_Html::create('div');
        $div->class = 'calender';
        if (0 < $date->getYear()->getTimestamp()) {
            $div->span(ucfirst($date->toString(
                    Zend_Date::WEEKDAY . ' ' . Zend_Date::DAY_SHORT . ' ' .
                    Zend_Date::MONTH_NAME . ' ' . Zend_Date::YEAR
                    )))->class = 'date';
            $div->br();
        }
        // $div->strong($date->toString());
        // $div->br();
        $div->span(sprintf($this->_('At: %s.'), $date->toString(Zend_Date::TIME_SHORT)))->class = 'time';
        return $div;
    }
}
