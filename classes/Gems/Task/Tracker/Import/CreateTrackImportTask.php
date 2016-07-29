<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CreateTrackImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 20, 2016 1:23:22 PM
 */
class CreateTrackImportTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Gems_Loader
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
    public function execute($formData = null)
    {
        $batch   = $this->getBatch();
        $import  = $batch->getVariable('import');
        $tracker = $this->loader->getTracker();

        $model = $tracker->getTrackModel();
        $model->applyFormatting(true, true);

        $trackData = $import['trackData'];
        $trackData['gtr_track_name'] = $formData['gtr_track_name'];
        $trackData['gtr_organizations'] = $formData['gtr_organizations'];

        // \MUtil_Echo::track($trackData);
        if ($trackData['gtr_date_start'] && (! $trackData['gtr_date_start'] instanceof \Zend_Date)) {
            $trackData['gtr_date_start'] = new \MUtil_Date($trackData['gtr_date_start'], 'yyyy-MM-dd');
        }
        $output = $model->save($trackData);

        $import['trackId'] = $output['gtr_id_track'];
        $import['trackData']['gtr_id_track'] = $output['gtr_id_track'];

        $batch->addMessage(sprintf($this->_('Created track with id %d'), $output['gtr_id_track']));
    }
}
