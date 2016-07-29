<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CheckTrackRoundImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 18, 2016 7:34:00 PM
 */
class CheckTrackRoundImportTask extends \MUtil_Task_TaskAbstract
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
     */
    public function execute($lineNr = null, $roundData = null)
    {
        $batch  = $this->getBatch();
        $events = $this->loader->getEvents();
        $import = $batch->getVariable('import');

        if (isset($roundData['gro_id_order']) && $roundData['gro_id_order']) {
            $import['roundOrder'][$roundData['gro_id_order']] = false;
        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                $this->_('No gro_id_order specified for round at line %d.'),
                $lineNr
                ));
        }
        if (isset($roundData['survey_export_code']) && $roundData['survey_export_code']) {
            if (! (isset($import['surveyCodes']) &&
                    array_key_exists($roundData['survey_export_code'], $import['surveyCodes']))) {
                $batch->addToCounter('import_errors');
                $batch->addMessage(sprintf(
                        $this->_('Unknown survey export code "%s" specified for round on line %d.'),
                        $roundData['survey_export_code'],
                        $lineNr
                        ));
            }
        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No survey export code specified for round on line %d.'),
                    $lineNr
                    ));
        }
        if (isset($roundData['gro_changed_event']) && $roundData['gro_changed_event']) {
            try {
                $events->loadRoundChangedEvent($roundData['gro_changed_event']);
            } catch (\Gems_Exception_Coding $ex) {
                $batch->addToCounter('import_errors');
                $batch->addMessage(sprintf(
                        $this->_('Unknown or invalid round changed event "%s" specified on line %d.'),
                        $roundData['gro_changed_event'],
                        $lineNr
                        ));
            }
        }
        if (isset($roundData['gro_display_event']) && $roundData['gro_display_event']) {
            try {
                $events->loadSurveyDisplayEvent($roundData['gro_display_event']);
            } catch (\Gems_Exception_Coding $ex) {
                $batch->addToCounter('import_errors');
                $batch->addMessage(sprintf(
                        $this->_('Unknown or invalid round display event "%s" specified on line %d.'),
                        $roundData['gro_display_event'],
                        $lineNr
                        ));
            }
        }
    }
}
