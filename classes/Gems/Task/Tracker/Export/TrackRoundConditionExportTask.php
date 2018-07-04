<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Export;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class TrackRoundConditionExportTask extends TrackExportAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($trackId = null)
    {
        $batch = $this->getBatch();
        $select = $this->db->select();

        // Join conditions on rounds, handle and / or conditions when implemented
        $select->from('gems__conditions', array(
            'gcon_id', 'gcon_type', 'gcon_class', 'gcon_name', 'gcon_condition_text1', 'gcon_condition_text2', 'gcon_condition_text3', 'gcon_condition_text4',
            ))  ->joinInner('gems__rounds', 'gems__conditions.gcon_id = gems__rounds.gro_condition', array())
                ->where('gems__rounds.gro_id_track = ?', $trackId);
        // \MUtil_Echo::track($select->__toString(), $roundId);

        $data = $this->db->fetchAll($select);
        // \MUtil_Echo::track($data);

        if ($data) {
            $this->exportTypeHeader('conditions');
            $this->exportFieldHeaders(reset($data));
            
            foreach($data as $row)
            {
                $this->exportFieldData($row);
                $count = $batch->addToCounter('conditions_exported');
            }
            $this->exportFlush();

            $batch->setMessage('conditions_export', sprintf(
                    $this->plural('%d condition exported', '%d conditions exported', $count),
                    $count
                    ));
        }
    }
}
