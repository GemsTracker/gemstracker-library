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

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 08-Jan-2020 11:50:40
 */
class AppointmentShortSnippet extends \Gems\Snippets\Agenda\AppointmentShowSnippet
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

    protected function createModel(): DataReaderInterface
    {
        parent::createModel();

        $this->model->getMetaModel()->del('gap_admission_time', 'formatFunction');
        $this->model->getMetaModel()->del('gap_discharge_time', 'formatFunction');

        foreach ($this->model->getMetaModel()->getColNames('label') as $name) {
            if (! in_array($name, $this->showFields)) {
                $this->model->getMetaModel()->del($name, 'label');
            }
        }

        return $this->model;
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param DetailTableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $dataModel)
    {
        // Do nothing
    }
}
