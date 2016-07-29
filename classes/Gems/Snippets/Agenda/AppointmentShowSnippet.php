<?php

/**
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
class Gems_Snippets_Agenda_AppointmentShowSnippet extends \Gems_Snippets_ModelItemTableSnippetAbstract
{
    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     * /
    protected function addShowTableRows(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        parent::addShowTableRows($bridge, $model);
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
            $this->model->applyDetailSettings();
        }
        $this->model->set('gap_admission_time', 'formatFunction', array($this, 'displayDate'));
        $this->model->set('gap_discharge_time', 'formatFunction', array($this, 'displayDate'));

        return $this->model;
    }

    public function displayDate($date)
    {
        if (! $date instanceof \Zend_Date) {
            return $date;
        }
        $div = \MUtil_Html::create('div');
        $div->class = 'calendar';
        if (0 < $date->getYear()->getTimestamp()) {
            $div->span(ucfirst($date->toString(
                    \Zend_Date::WEEKDAY . ' ' . \Zend_Date::DAY_SHORT . ' ' .
                    \Zend_Date::MONTH_NAME . ' ' . \Zend_Date::YEAR
                    )))->class = 'date';
        }
        // $div->strong($date->toString());
        // $div->br();
        $td = $div->span($date->toString(\Zend_Date::TIME_SHORT));
        $td->class = 'time middleAlign';
        $td->append(' ');
        $td->img()->src = 'stopwatch.png';
        return $div;
    }
}
