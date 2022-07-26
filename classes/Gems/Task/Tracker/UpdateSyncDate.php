<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 7-mei-2015 18:33:11
 */
class UpdateSyncDate extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $userId = null)
    {
        $now    = new \MUtil\Db\Expr\CurrentTimestamp();
        $values = array('gso_last_synch' => $now, 'gso_changed' => $now, 'gso_changed_by' => $userId);
        $where  = $this->db->quoteInto('gso_id_source = ?', $sourceId);

        $this->db->update('gems__sources', $values, $where);
    }
}
