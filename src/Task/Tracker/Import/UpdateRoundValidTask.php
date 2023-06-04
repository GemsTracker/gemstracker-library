<?php

/**
 *
 * @package    Gems
 * @subpackage UpdateRoundValidTask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage UpdateRoundValidTask
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 21, 2016 12:51:01 PM
 */
class UpdateRoundValidTask extends \MUtil\Task\TaskAbstract
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
    public function execute($lineNr = null, $forRoundOrder = null, $usesRoundRound = null, $roundField = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (! (isset($import['trackId']) && $import['trackId'])) {
            // Do nothing
            return;
        }

        if (isset($import['roundOrders'][$usesRoundRound]) &&
                $import['roundOrders'][$usesRoundRound]) {

            $this->db->update(
                    'gems__rounds',
                    array($roundField => $import['roundOrders'][$usesRoundRound]),
                    $this->db->quoteInto("gro_id_order = ? AND ", $forRoundOrder) .
                        $this->db->quoteInto("gro_id_track = ?", $import['trackId'])
                    );
        }
    }
}
