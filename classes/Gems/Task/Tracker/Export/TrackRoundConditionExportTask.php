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
                ->where('gems__rounds.gro_id_track = ?', $trackId)
                ->distinct(true);
        // \MUtil\EchoOut\EchoOut::track($select->__toString(), $roundId);

        $data = $this->db->fetchAll($select);
        // \MUtil\EchoOut\EchoOut::track($data);

        if ($data) {
            // We need to find single conditions and nested conditions
            $single = [];
            $nested = [];
            foreach($data as $row)
            {
                if (preg_match('/.*(AndCondition|OrCondition)$/', $row['gcon_class']) == 1) {                    
                    // We have a nested condition
                    $this->resolveCondition($row, $single, $nested);
                } else {
                    $single[$row['gcon_id']] = $row;
                }
            }
            $this->exportTypeHeader('conditions');
            $this->exportFieldHeaders(reset($data));
            
            $conditions = $single + $nested;
            
            foreach($conditions as $row)
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
    
    public function resolveCondition($condition, &$single, &$nested)
    {
        $requests = [
            $condition['gcon_condition_text1'],
            $condition['gcon_condition_text2'],
            $condition['gcon_condition_text3'],
            $condition['gcon_condition_text4']
        ];
        
        $requests = array_filter($requests);

        foreach($requests as $conditionId)
        {
            if (!array_key_exists($conditionId, $single) && !array_key_exists($conditionId, $nested)) {
                $select = $this->db->select()->from('gems__conditions', array(
                    'gcon_id', 'gcon_type', 'gcon_class', 'gcon_name', 'gcon_condition_text1', 'gcon_condition_text2', 'gcon_condition_text3', 'gcon_condition_text4',
                    ))->where('gcon_id = ?', $conditionId);
                
                $data = $this->db->fetchRow($select);
                if ($data) {
                    if (preg_match('/.*(AndCondition|OrCondition)$/', $data['gcon_class']) == 1) {                    
                        // We have a nested condition
                        $this->resolveCondition($data, $single, $nested);
                    } else {
                        $single[$data['gcon_id']] = $data;
                    }
                }
            }
        }
        $nested[$condition['gcon_id']] = $condition;
    }
}
