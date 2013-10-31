<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CalenderTableSnippet.php$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Agenda_CalenderTableSnippet extends Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

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
        $table = $bridge->getTable();
        $table->appendAttrib('class', 'calender');
        $table->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);

        // Row with dates and patient data
        $table->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $bridge->addSortable('date_only', $this->_('Date'), array('class' => 'date'))->colspan = 4;

        // Row with dates and patient data
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $bridge->addSortable('gr2o_patient_nr');
        $bridge->addSortable('name');
        $bridge->addSortable('gor_name');
        $bridge->addSortable('glo_name');

        $link = null;

        $bridge->tr(array('class' => 'odd'));
        $bridge->addItemLink($link);
        $bridge->addSortable('gap_admission_time');
        $bridge->addSortable('gaa_name');
        $bridge->addSortable('gapr_name');
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
            $this->model->applyBrowseSettings();
        }
        $this->model->addColumn(new Zend_Db_Expr("CONVERT(gap_admission_time, DATE)"), 'date_only');
        $this->model->set('date_only', 'dateFormat',
                    Zend_Date::WEEKDAY . ' ' . Zend_Date::DAY_SHORT . ' ' .
                    Zend_Date::MONTH_NAME . ' ' . Zend_Date::YEAR);
        $this->model->set('gap_admission_time', 'label', $this->_('Time'),
                'dateFormat', 'hh:mm');

        $this->model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        Gems_Model_RespondentModel::addNameToModel($this->model, $this->_('Name'));

        // MUtil_Model::$verbose = true;
        return $this->model;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        parent::processFilterAndSort($model);

        $params = $this->requestCache->getProgramParams() + $this->defaultSearchData;

        if ($where = Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($params, $this->db)) {
            $filter[] = $where;
            $model->addFilter($filter);
        }

    }
}
