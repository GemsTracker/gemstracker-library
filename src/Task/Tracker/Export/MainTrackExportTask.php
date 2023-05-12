<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
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
        $this->exportTypeHeader('track');
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
