<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 08-Jan-2020 11:50:40
 */
class AppointmentShortSnippet extends \Gems_Snippets_Agenda_AppointmentShowSnippet
{
    /**
     *
     * @var array containing the field names to display
     */
    protected $showFields = [
        'gap_admission_time',
        'gap_status',
        'gap_id_attended_by',
        ];

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();

        $this->model->del('gap_admission_time', 'formatFunction');
        $this->model->del('gap_discharge_time', 'formatFunction');
        
        foreach ($model->getColNames('label') as $name) {
            if (! in_array($name, $this->showFields)) {
                $model->del($name, 'label');
            }
        }

        return $model;
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Do nothing
    }
}
