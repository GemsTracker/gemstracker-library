<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\AppointmentFilterInterface;
use Gems\Html;
use Gems\MenuNew\RouteHelper;
use Gems\Model;
use MUtil\Lazy\Call;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class CalendarTableSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems\Agenda\AppointmentFilterInterface
     */
    protected $calSearchFilter;


    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $bridge->gr2o_id_organization;

        $appointmentParams = [
            Model::APPOINTMENT_ID => $bridge->getLazy('gap_id_appointment'),
        ];

        $appointmentHref = new Call(function(string $routeName, array $params = []) {
            return $this->routeHelper->getRouteUrl($routeName, $params);
        }, ['calendar.show', $appointmentParams]);

        $appButton = null;
        if ($appointmentHref) {
            $appButton = Html::actionLink($appointmentHref, $this->_('Show appointment'));//$menuItem->toActionLink($this->request, $bridge, $this->_('Show appointment'));
        }

        $respondentParams = [
            'id1' => $bridge->getLazy('gr2o_patient_nr'),
            'id2' => $bridge->getLazy('gr2o_id_organization'),
        ];

        $respondentHref = new Call(function(string $routeName, array $params = []) {
            return $this->routeHelper->getRouteUrl($routeName, $params);
        }, ['respondent.show', $respondentParams]);

        $respondentButton = null;
        if ($respondentHref) {
            $respondentButton = Html::actionLink($appointmentHref, $this->_('Show appointment'));//$menuItem->toActionLink($this->request, $bridge, $this->_('Show appointment'));
        }

        $br = \MUtil\Html::create('br');
        $sp = \MUtil\Html::raw(' ');

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
        $bridge->addColumn($respondentButton)->class = 'middleAlign rightAlign';

        unset($table[\MUtil\Html\TableElement::THEAD]);
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
            $this->bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_ROWS;
            $this->caption    = $this->_('Example appointments');

            if ($this->calSearchFilter instanceof AppointmentFilterInterface) {
                $this->searchFilter = [
                    \MUtil\Model::SORT_DESC_PARAM => 'gap_admission_time',
                    $this->calSearchFilter->getSqlAppointmentsWhere(),
                    'limit' => 10,
                ];
                // \MUtil\EchoOut\EchoOut::track($this->calSearchFilter->getSqlAppointmentsWhere());

                $this->onEmpty = $this->_('No example appointments found');
            } elseif (false === $this->calSearchFilter) {
                $this->onEmpty = $this->_('Filter is inactive');
                $this->searchFilter = ['1=0'];
            } elseif (is_array($this->calSearchFilter)) {
                $this->searchFilter = $this->calSearchFilter;
            }
        }
        // \MUtil\EchoOut\EchoOut::track($this->calSearchFilter);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof \Gems\Model\AppointmentModel) {
            $this->model = $this->loader->getModels()->createAppointmentModel();
            $this->model->applyBrowseSettings();
        }
        $this->model->addColumn(new \Zend_Db_Expr("CONVERT(gap_admission_time, DATE)"), 'date_only');
        $this->model->set('date_only', 'type', \MUtil\Model::TYPE_DATE);
        $this->model->set('gap_admission_time', 'label', $this->_('Time'), 'type', \MUtil\Model::TYPE_TIME);

        $this->model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        \Gems\Model\RespondentModel::addNameToModel($this->model, $this->_('Name'));

        $this->model->refreshGroupSettings();

        // \MUtil\Model::$verbose = true;
        return $this->model;
    }
}
