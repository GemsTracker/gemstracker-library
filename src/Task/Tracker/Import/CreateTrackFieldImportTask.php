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

use Gems\Tracker\Engine\FieldsDefinition;
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
class CreateTrackFieldImportTask extends \MUtil\Task\TaskAbstract
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

        if (! (isset($import['trackId']) && $import['trackId'])) {
            // Do nothing
            return;
        }

        $tracker     = $this->loader->getTracker();
        $trackEngine = $tracker->getTrackEngine($import['trackId']);
        $fieldModel  = $trackEngine->getFieldsMaintenanceModel(true, 'create');

        $fieldData['gtf_id_track'] = $import['trackId'];

        $fieldData = $fieldModel->save($fieldData);

        // Store the field reference
        $import['fieldCodes']['{f' . $fieldData['gtf_id_order'] . '}'] = FieldsDefinition::makeKey(
                $fieldData['sub'],
                $fieldData['gtf_id_field']
                );

        if (isset($fieldData['gtf_calculate_using']) && $fieldData['gtf_calculate_using']) {
            $batch->addTask(
                    'Tracker\\Import\\UpdateFieldCalculationTask',
                    $lineNr,
                    $fieldData['gtf_id_field'],
                    $fieldModel->getModelNameForRow($fieldData),
                    $fieldData['gtf_calculate_using']
                    );
        }
        $batch->setVariable('import', $import);
    }
}
