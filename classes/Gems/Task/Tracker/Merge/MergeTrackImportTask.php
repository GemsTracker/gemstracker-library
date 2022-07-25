<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Merge;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 1, 2016 6:39:10 PM
 */
class MergeTrackImportTask extends \MUtil\Task\TaskAbstract
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

        if ($batch->hasVariable('trackEngine')) {
            $trackEngine = $batch->getVariable('trackEngine');
            if ($trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
                $trackData['gtr_id_track'] = $trackEngine->getTrackId();
            }
        }

        // \MUtil\EchoOut\EchoOut::track($trackData);
        if ($trackData['gtr_date_start'] && (! $trackData['gtr_date_start'] instanceof \Zend_Date)) {
            $trackData['gtr_date_start'] = new \MUtil\Date($trackData['gtr_date_start'], 'yyyy-MM-dd');
        }
        $output = $model->save($trackData);

        $import['trackId'] = $output['gtr_id_track'];
        $import['trackData']['gtr_id_track'] = $output['gtr_id_track'];

        $batch->addMessage($this->_('Merged track data'));
    }
}
