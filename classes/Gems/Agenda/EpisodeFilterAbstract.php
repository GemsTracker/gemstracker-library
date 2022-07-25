<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:13:01
 */
abstract class EpisodeFilterAbstract extends BasicFilterAbstract
{
    /**
     * Generate a where statement to filter an appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere()
    {
        $where = $this->getSqlEpisodeWhere();

        if (($where == parent::NO_MATCH_SQL) || ($where == parent::MATCH_ALL_SQL)) {
            return $where;
        }

        return sprintf(
                "gap_id_episode IN (SELECT gec_episode_of_care_id FROM gems__episodes_of_care WHERE %s)",
                $where
                );
    }

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    // public function getSqlEpisodeWhere();

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems\Agenda\Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(\Gems\Agenda\Appointment $appointment)
    {
        $episode = $appointment->getEpisode();

        if (! ($episode && $episode->exists)) {
            return false;
        }

        return $this->matchEpisode($episode);
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    // public function matchEpisode(EpisodeOfCare $episode);

    /**
     *
     * @return boolean When true prefer SQL statements filtering appointments
     */
    public function preferAppointmentSql()
    {
        return false;
    }
}
