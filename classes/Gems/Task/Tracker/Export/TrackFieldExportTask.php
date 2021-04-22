<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackFieldExportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Export;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Field\FieldAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 12, 2016 5:31:00 PM
 */
class TrackFieldExportTask extends TrackExportAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($trackId = null, $fieldKey = null)
    {
        $batch  = $this->getBatch();
        $engine = $this->loader->getTracker()->getTrackEngine($trackId);
        $fields = $engine->getFieldsDefinition();
        $model  = $fields->getMaintenanceModel();
        $filter = FieldsDefinition::splitKey($fieldKey);

        $filter['gtf_id_track'] = $trackId;
        $data = $model->loadFirst($filter);
        // \MUtil_Echo::track($fieldKey, $data);

        if ($data) {
            unset($data['sub'], $data['gtf_id_field'], $data['gtf_id_track'],
                    $data['gtf_filter_id'], // TODO: Export track filters
                    $data['gtf_field_value_keys'],
                    $data['gtf_changed'], $data['gtf_changed_by'], $data['gtf_created'], $data['gtf_created_by'],
                    $data['calculation'], $data['htmlUse'], $data['htmlCalc'], $data['htmlCreate']);

            if (isset($data['gtf_calculate_using']) && $data['gtf_calculate_using']) {
                $calcs = explode(FieldAbstract::FIELD_SEP, $data['gtf_calculate_using']);
                foreach ($calcs as &$key) {
                    $key = $this->translateFieldCode($fields, $key);
                }
                $data['gtf_calculate_using'] = implode(FieldAbstract::FIELD_SEP, $calcs);
            }

            $count = $batch->addToCounter('fields_exported');
            if ($count == 1) {
                $this->exportTypeHeader('fields');
            }
            // The number and order of fields can change per field and installation
            $this->exportFieldHeaders($data);
            $this->exportFieldData($data);
            $this->exportFlush();

            $batch->setMessage('fields_export', sprintf(
                    $this->plural('%d field exported', '%d fields exported', $count),
                    $count
                    ));

        }
    }
}
