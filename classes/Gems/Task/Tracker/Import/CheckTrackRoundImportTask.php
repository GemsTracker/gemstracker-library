<?php

/**
 * Copyright (c) 2015, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
