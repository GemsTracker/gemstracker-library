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
 * @version    $Id: MainTrackExportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Export;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 12, 2016 2:53:09 PM
 */
class MainTrackExportTask extends TrackExportAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($trackId = null, $exportOrganizations = false)
    {
        $batch = $this->getBatch();
        $data  = $this->db->fetchRow("SELECT * FROM gems__tracks WHERE gtr_id_track = ?", $trackId);
        $orgs  = $exportOrganizations ? $data['gtr_organizations'] : false;

        unset($data['gtr_id_track'], $data['gtr_track_type'], $data['gtr_active'], $data['gtr_survey_rounds'], $data['gtr_organizations'],
                $data['gtr_changed'],
                $data['gtr_changed_by'], $data['gtr_created'], $data['gtr_created_by']);

        // Main track data
        $this->exportTypeHeader('track', false);
        $this->exportFieldHeaders($data);
        $this->exportFieldData($data);

        if ($orgs) {
            // Organizations
            $this->exportTypeHeader('organizations');
            $this->exportFieldHeaders(array('gor_id_organization' => null, 'gor_name' => null));
            foreach (explode('|', $orgs) as $orgId) {
                if ($orgId) {
                    $org = $this->loader->getOrganization($orgId);
                    if ($org) {
                        $this->exportFieldData(array($orgId, $org->getName()));
                    }
                }
            }
            $batch->addMessage($this->_('Trackdata exported with organizations.'));
        } else {
            $batch->addMessage($this->_('Trackdata exported without organizations.'));
        }

        $this->exportFlush();
    }
}
