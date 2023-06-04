<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

use Gems\Tracker\Field\FieldInterface;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 18, 2016 7:34:00 PM
 */
class CheckTrackFieldImportTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param array $trackData Nested array of trackdata
     */
    public function execute($lineNr = null, $fieldData = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (isset($fieldData['gtf_id_order']) && $fieldData['gtf_id_order']) {
            $import['fieldOrder'][$fieldData['gtf_id_order']] = false;

            if ($batch->hasVariable('trackEngine') &&
                    isset($fieldData['gtf_field_type']) &&
                    $fieldData['gtf_field_type']) {

                $trackEngine = $batch->getVariable('trackEngine');
                if ($trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
                    $fieldDef = $trackEngine->getFieldsDefinition();
                    $field    = $fieldDef->getFieldByOrder($fieldData['gtf_id_order']);

                    if ($field instanceof FieldInterface) {
                        if ($field->getFieldType() != $fieldData['gtf_field_type']) {
                            $batch->addToCounter('import_errors');
                            $batch->addMessage(sprintf(
                                    $this->_('Conflicting field types "%s" and "%s" for field orders %d specified on line %d.'),
                                    $field->getFieldType(),
                                    $fieldData['gtf_field_type'],
                                    $fieldData['gtf_id_order'],
                                    $lineNr
                                    ));
                        }
                    }
                }
            }

        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gtf_id_order specified for field at line %d.'),
                    $lineNr
                    ));
        }
        if (isset($fieldData['gtf_field_type']) && $fieldData['gtf_field_type']) {
            $model = $this->loader->getTracker()->createTrackClass('Model\\FieldMaintenanceModel');

            $fields = $model->getFieldTypes();

            if (! isset($fields[$fieldData['gtf_field_type']])) {
                    $batch->addToCounter('import_errors');
                    $batch->addMessage(sprintf(
                            $this->_('Unknown field type "%s" specified on line %d.'),
                            $fieldData['gtf_field_type'],
                            $lineNr
                            ));
            }

        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No field type specified on line %d.'),
                    $lineNr
                    ));
        }
        $batch->setVariable('import', $import);
    }
}
