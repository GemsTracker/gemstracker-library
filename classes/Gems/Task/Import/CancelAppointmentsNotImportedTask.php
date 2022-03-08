<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Import;

/**
 *
 * @package    Gems
 * @subpackage Task\Import
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class CancelAppointmentsNotImportedTask extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;
    
    /**
     * @inheritDoc
     */
    public function execute($source = null, $cancelCode = null, $startSynchDate = null, $onlyFutureAppointments = true)
    {
        if ($source && $cancelCode && $startSynchDate) {
            $values = ["gap_status" => $cancelCode];
            
            $where = $this->db->quoteInto("gap_source = ?", $source);
            $where .= $this->db->quoteInto(" AND gap_status != ?", $cancelCode);
            $where .= $this->db->quoteInto(" AND gap_last_synch < ?", $startSynchDate);
            if ($onlyFutureAppointments) {
                $where .= " AND gap_admission_time > CURRENT_TIMESTAMP";
            }
            
            // \MUtil_Echo::track($values, $where);            
            $deleted = $this->db->update('gems__appointments', $values, $where);
            
            $this->getBatch()->addMessage(sprintf($this->_('%s appointment(s) deleted.'), $deleted));
        }
    }
}