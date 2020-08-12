<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CalendarTableSnippet.php$
 */

use Gems\Agenda\AppointmentFilterInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Agenda_CalendarTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems\Agenda\AppointmentFilterInterface
     */
    protected $calSearchFilter;

    
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
        $bridge->gr2o_id_organization;

        if ($menuItem = $this->menu->find(array('controller' => 'appointment', 'action' => 'show', 'allowed' => true))) {
            $appButton = $menuItem->toActionLink($this->request, $bridge, $this->_('Show appointment'));
        } else {
            $appButton = null;
        }
        if ($menuItem = $this->menu->find(array('controller' => 'respondent', 'action' => 'show', 'allowed' => true))) {
            $respButton = $menuItem->toActionLink($this->request, $bridge, $this->_('Show respondent'));
        } else {
            $respButton = null;
        }

        $br = \MUtil_Html::create('br');
        $sp = \MUtil_Html::raw(' ');

        $table = $bridge->getTable();
        $table->appendAttrib('class', 'calendar');
        $table->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);

        // Row with dates and patient data
        $table->tr(array('onlyWhenChanged' => true, 'class' => 'date'));
        $bridge->addSortable('date_only', $this->_('Date'), array('class' => 'date'))->colspan = 4;

        // Row with dates and patient data
        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'time middleAlign'));
        $td = $bridge->addSortable('gap_admission_time');
        $td->append(' ');
        $td->img()->src = 'stopwatch.png';
        $td->title = $bridge->date_only; // Add title, to make sure row displays when time is same as time for previous day
        $bridge->addSortable('gor_name');
        $bridge->addSortable('glo_name')->colspan = 2;

        $bridge->tr()->class = array('odd', $bridge->row_class);
        $bridge->addColumn($appButton)->class = 'middleAlign';
        $bridge->addMultiSort('gr2o_patient_nr', $sp, 'gap_subject', $br, 'name');
        // $bridge->addColumn(array($bridge->gr2o_patient_nr, $br, $bridge->name));
        $bridge->addMultiSort(array($this->_('With')), array(' '), 'gas_name', $br, 'gaa_name', array(' '), 'gapr_name');
        // $bridge->addColumn(array($bridge->gaa_name, $br, $bridge->gapr_name));
        $bridge->addColumn($respButton)->class = 'middleAlign rightAlign';

        unset($table[\MUtil_Html_TableElement::THEAD]);
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

        if (null !== $this->calSearchFilter) {
            $this->bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_ROWS;
            $this->caption    = $this->_('Example appointments');
            
            if ($this->calSearchFilter instanceof AppointmentFilterInterface) {
                $this->searchFilter = [
                    \MUtil_Model::SORT_DESC_PARAM => 'gap_admission_time',
                    $this->calSearchFilter->getSqlAppointmentsWhere(),
                    'limit' => 10,
                    ];
                // \MUtil_Echo::track($this->calSearchFilter->getSqlAppointmentsWhere());

                $this->onEmpty = $this->_('No example appointments found');
            } elseif (false === $this->calSearchFilter) {
                $this->onEmpty = $this->_('Filter is inactive');
                $this->searchFilter = ['1=0'];
            } elseif (is_array($this->calSearchFilter)) {
                $this->searchFilter = $this->calSearchFilter;
            }
        }
        // \MUtil_Echo::track($this->calSearchFilter);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof \Gems_Model_AppointmentModel) {
            $this->model = $this->loader->getModels()->createAppointmentModel();
            $this->model->applyBrowseSettings();
        }
        $this->model->addColumn(new \Zend_Db_Expr("CONVERT(gap_admission_time, DATE)"), 'date_only');
        $this->model->set('date_only', 'dateFormat',
                \Zend_Date::WEEKDAY . ' ' . \Zend_Date::DAY_SHORT . ' ' .
                \Zend_Date::MONTH_NAME . ' ' . \Zend_Date::YEAR,
                'storageFormat', 'YYYY-MM-DD'
                );
        $this->model->set('gap_admission_time', 'label', $this->_('Time'),
                'dateFormat', 'HH:mm');

        $this->model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        \Gems_Model_RespondentModel::addNameToModel($this->model, $this->_('Name'));

        $this->model->refreshGroupSettings();

        // \MUtil_Model::$verbose = true;
        return $this->model;
    }
}
