<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CheckTrackImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 18, 2016 6:55:45 PM
 */
class CheckTrackImportTask extends \MUtil_Task_TaskAbstract
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
    public function execute($tracksData = null)
    {
        $batch = $this->getBatch();

        switch (count((array) $tracksData)) {
            case 0:
                $batch->addToCounter('import_errors');
                $batch->addMessage($this->_('No "track" data found in import file.'));
                break;

            case 1;
                $trackData = reset($tracksData);
                $lineNr    = key($tracksData);

                $defaults  = array('gtr_track_name', 'gtr_track_info', 'gtr_code', 'gtr_date_start', 'gtr_date_until');
                $events    = $this->loader->getEvents();
                $import    = $batch->getVariable('import');
                $tracker   = $this->loader->getTracker();

                $import['trackData'] = $trackData;

                foreach ($defaults as $name) {
                    if (isset($trackData[$name])) {
                        $import['formDefaults'][$name] = $trackData[$name];
                        $import['modelSettings'][$name]['respondentData'] = true;
                    }
                }
                if ($batch->hasVariable('trackEngine') && isset($trackData['gtr_track_name'])) {
                    $trackEngine = $batch->getVariable('trackEngine');
                    if ($trackEngine->getTrackName() == $trackData['gtr_track_name']) {
                        $import['modelSettings']['gtr_track_name']['elementClass'] = 'Exhibitor';
                    } else {
                        $import['modelSettings']['gtr_track_name']['description'] = sprintf(
                                $this->_('Current track name is "%s".'),
                                $trackEngine->getTrackName()
                                );
                    }
                }

                if (isset($trackData['gtr_track_class']) && $trackData['gtr_track_class']) {
                    $trackEngines = $tracker->getTrackEngineClasses();
                    if (! isset($trackEngines[$trackData['gtr_track_class']])) {
                        $batch->addToCounter('import_errors');
                        $batch->addMessage(sprintf(
                                $this->_('Unknown track engine "%s" specified on line %d.'),
                                $trackData['gtr_track_class'],
                                $lineNr
                                ));
                    }
                } else {
                    $batch->addToCounter('import_errors');
                    $batch->addMessage(sprintf(
                            $this->_('No track engine specified on line %d.'),
                            $lineNr
                            ));
                }
                if (isset($trackData['gtr_beforefieldupdate_event']) && $trackData['gtr_beforefieldupdate_event']) {
                    try {
                        $events->loadBeforeTrackFieldUpdateEvent($trackData['gtr_beforefieldupdate_event']);
                    } catch (\Gems_Exception_Coding $ex) {
                        $batch->addToCounter('import_errors');
                        $batch->addMessage(sprintf(
                                $this->_('Unknown or invalid track field before update event "%s" specified on line %d.'),
                                $trackData['gtr_beforefieldupdate_event'],
                                $lineNr
                                ));
                    }
                }
                if (isset($trackData['gtr_calculation_event']) && $trackData['gtr_calculation_event']) {
                    try {
                        $events->loadTrackCalculationEvent($trackData['gtr_calculation_event']);
                    } catch (\Gems_Exception_Coding $ex) {
                        $batch->addToCounter('import_errors');
                        $batch->addMessage(sprintf(
                                $this->_('Unknown or invalid track calculation event "%s" specified on line %d.'),
                                $trackData['gtr_calculation_event'],
                                $lineNr
                                ));
                    }
                }
                if (isset($trackData['gtr_completed_event']) && $trackData['gtr_completed_event']) {
                    try {
                        $events->loadTrackCompletionEvent($trackData['gtr_completed_event']);
                    } catch (\Gems_Exception_Coding $ex) {
                        $batch->addToCounter('import_errors');
                        $batch->addMessage(sprintf(
                                $this->_('Unknown or invalid track completion event "%s" specified on line %d.'),
                                $trackData['gtr_completed_event'],
                                $lineNr
                                ));
                    }
                }
                if (isset($trackData['gtr_fieldupdate_event']) && $trackData['gtr_fieldupdate_event']) {
                    try {
                        $events->loadTrackFieldUpdateEvent($trackData['gtr_fieldupdate_event']);
                    } catch (\Gems_Exception_Coding $ex) {
                        $batch->addToCounter('import_errors');
                        $batch->addMessage(sprintf(
                                $this->_('Unknown or invalid track field update event "%s" specified on line %d.'),
                                $trackData['gtr_fieldupdate_event'],
                                $lineNr
                                ));
                    }
                }
                break;

            default:
                $batch->addToCounter('import_errors');
                $batch->addMessage(sprintf(
                        $this->_('%d sets of "track" data found in import file.'),
                        count($tracksData)
                        ));
                foreach ($tracksData as $lineNr => $trackData) {
                    $batch->addMessage(sprintf(
                            $this->_('"track" data found on line %d.'),
                            $lineNr
                            ));
                }
        }
        $batch->setVariable('import', $import);
    }
}
