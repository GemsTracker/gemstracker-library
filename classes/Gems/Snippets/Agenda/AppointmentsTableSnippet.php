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
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Agenda_AppointmentsTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Date storage format string
     *
     * @var string
     */
    private $_dateStorageFormat;

    /**
     * Image for time display
     *
     * @var \MUtil_Html_HtmlElement
     * /
    private $_timeImg;

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
    public $menuActionController = 'appointment';

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

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
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;

        if ($menuItem = $this->menu->find(array('controller' => 'appointment', 'action' => 'show', 'allowed' => true))) {
            $appButton = $menuItem->toActionLink($this->request, $bridge, $this->_('Show appointment'));
        } else {
            $appButton = null;
        }
        if ($menuItem = $this->menu->find(array('controller' => 'appointment', 'action' => 'edit', 'allowed' => true))) {
            $editButton = $menuItem->toActionLink($this->request, $bridge, $this->_('Edit appointment'));
        } else {
            $editButton = null;
        }

        $br    = \MUtil_Html::create('br');

        $table = $bridge->getTable();
        $table->appendAttrib('class', 'calendar');

        $bridge->tr()->appendAttrib('class', $bridge->row_class);
        if ($appButton) {
            $bridge->addItemLink($appButton)->class = 'middleAlign';
        }
        if ($this->sortableLinks) {
            $bridge->addMultiSort(array($bridge->date_only), $br, 'gap_admission_time')->class = 'date';
            $bridge->addMultiSort('gap_subject', $br, 'glo_name');
            $bridge->addMultiSort('gaa_name', $br, 'gapr_name');
            $bridge->addMultiSort('gor_name', $br, 'glo_name');
        } else {
            $bridge->addMultiSort(
                    array($bridge->date_only),
                    $br,
                    array($bridge->gap_admission_time, $model->get('gap_admission_time', 'label'))
                    );
            $bridge->addMultiSort(
                    array($bridge->gap_subject, $model->get('gap_subject', 'label')),
                    $br,
                    array($bridge->gas_name, $model->get('gas_name', 'label'))
                    );
            $bridge->addMultiSort(
                    array($bridge->gaa_name, $model->get('gaa_name', 'label')),
                    $br,
                    array($bridge->gapr_name, $model->get('gapr_name', 'label'))
                    );
            $bridge->addMultiSort(
                    array($bridge->gor_name, $model->get('gor_name', 'label')),
                    $br,
                    array($bridge->glo_name, $model->get('glo_name', 'label'))
                    );
        }
        if ($editButton) {
            $bridge->addItemLink($editButton)->class = 'middleAlign rightAlign';
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->onEmpty = $this->_('No appointments found.');
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof \Gems_Model_AppointmentModel) {
            $model = $this->model;
        } else {
            $model = $this->loader->getModels()->createAppointmentModel();
            $model->applyBrowseSettings();
        }

        $model->addColumn(new \Zend_Db_Expr("CONVERT(gap_admission_time, DATE)"), 'date_only');
        $model->set('date_only', 'formatFunction', array($this, 'formatDate'));
                // 'dateFormat', \Zend_Date::DAY_SHORT . ' ' . \Zend_Date::MONTH_NAME . ' ' . \Zend_Date::YEAR);
        $model->set('gap_admission_time', 'label', $this->_('Time'),
                'formatFunction', array($this, 'formatTime'));
                // 'dateFormat', 'HH:mm ' . \Zend_Date::WEEKDAY);

        $this->_dateStorageFormat = $model->get('gap_admission_time', 'storageFormat');
        // $this->_timeImg           = \MUtil_Html::create('img', array('src' => 'stopwatch.png', 'alt' => ''));

        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        if ($this->respondent instanceof \Gems_Tracker_Respondent) {
            $model->addFilter(array(
                'gap_id_user' => $this->respondent->getId(),
                'gap_id_organization' => $this->respondent->getOrganizationId(),
                ));
        }

        return $model;
    }

    /**
     * Display the date field
     *
     * @param \MUtil_Date $value
     */
    public function formatDate($value)
    {
        return \MUtil_Html::create(
                'span',
                // array('class' => 'date'),
                \MUtil_Date::format(
                        $value,
                        \Zend_Date::DAY_SHORT . ' ' . \Zend_Date::MONTH_NAME_SHORT . ' ' . \Zend_Date::YEAR,
                        $this->_dateStorageFormat
                        )
                );
    }

    /**
     * Display the time field
     *
     * @param \MUtil_Date $value
     */
    public function formatTime($value)
    {
        return \MUtil_Html::create(
                'span',
                // array('class' => 'time'),
                // $this->_timeImg,
                \MUtil_Date::format($value, ' HH:mm ' . \Zend_Date::WEEKDAY_SHORT, $this->_dateStorageFormat)
                );
    }
}
