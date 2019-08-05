<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackRoundExportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Export;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 12, 2016 5:31:00 PM
 */
class TrackRoundExportTask extends TrackExportAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($trackId = null, $roundId = null)
    {
        $batch = $this->getBatch();
        $select = $this->db->select();

        $select->from('gems__rounds', array(
            'gro_id_order', 'gro_id_relationfield', 'gro_round_description', 'gro_icon_file',
            'gro_changed_event', 'gro_display_event',
            'gro_valid_after_source', 'gro_valid_after_field', 'gro_valid_after_unit', 'gro_valid_after_length',
            'gro_valid_for_source', 'gro_valid_for_field', 'gro_valid_for_unit', 'gro_valid_for_length',
            'gro_organizations', 'gro_code', 'gro_condition'
            ))  ->joinInner('gems__surveys', 'gems__rounds.gro_id_survey = gsu_id_survey', array(
                'survey_export_code' => 'gsu_export_code',
                )) // gro_id_survey
                ->joinLeft('gems__rounds AS va', 'gems__rounds.gro_valid_after_id = va.gro_id_round', array(
                    'valid_after' => 'va.gro_id_order',
                    )) // gro_valid_after_id
                ->joinLeft('gems__rounds AS vf', 'gems__rounds.gro_valid_for_id = vf.gro_id_round', array(
                     'valid_for' => 'vf.gro_id_order',
                    )) // gro_valid_for_id
                ->where('gems__rounds.gro_id_round = ?', $roundId);
        // \MUtil_Echo::track($select->__toString(), $roundId);

        $data = $this->db->fetchRow($select);
        // \MUtil_Echo::track($data);

        if ($data) {
            $fields = $this->loader->getTracker()->getTrackEngine($trackId)->getFieldsDefinition();
            $tests  = [
                \Gems_Tracker_Engine_StepEngineAbstract::APPOINTMENT_TABLE,
                \Gems_Tracker_Engine_StepEngineAbstract::RESPONDENT_TRACK_TABLE,
                \Gems_Tracker_Engine_StepEngineAbstract::RESPONDENT_TABLE,
                ];
            if (isset($data['gro_id_relationfield']) && $data['gro_id_relationfield']) {
                // -1 means the respondent itself, also gro_id_relationfield stores a "bare"
                // field id, not one with a table prefix
                if ($data['gro_id_relationfield'] >= 0) {
                    $data['gro_id_relationfield'] = $this->translateFieldCode(
                            $fields,
                            FieldsDefinition::makeKey(FieldMaintenanceModel::FIELDS_NAME, $data['gro_id_relationfield'])
                            );
                }
            }

            if (isset($data['gro_valid_after_source'], $data['gro_valid_after_field']) &&
                    in_array($data['gro_valid_after_source'], $tests)) {
                // Translate field to {order}
                $data['gro_valid_after_field'] = $this->translateFieldCode($fields, $data['gro_valid_after_field']);
            }
            if (isset($data['gro_valid_for_source'], $data['gro_valid_for_field']) &&
                    in_array($data['gro_valid_for_source'], $tests)) {
                // Translate field to {order}
                $data['gro_valid_for_field'] = $this->translateFieldCode($fields, $data['gro_valid_for_field']);
            }

            $count = $batch->addToCounter('rounds_exported');

            if ($count == 1) {
                $this->exportTypeHeader('rounds');
                $this->exportFieldHeaders($data);
            }
            $this->exportFieldData($data);
            $this->exportFlush();

            $batch->setMessage('rounds_export', sprintf(
                    $this->plural('%d round exported', '%d rounds exported', $count),
                    $count
                    ));
        }
    }
}
