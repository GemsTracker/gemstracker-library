<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\Appointment;
use Gems\Agenda\EpisodeOfCare;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:13:01
 */
abstract class AppointmentFilterAbstract extends BasicFilterAbstract
{
    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere(): string
    {
        $where = $this->getSqlAppointmentsWhere();

        if (($where == static::NO_MATCH_SQL) || ($where == static::MATCH_ALL_SQL)) {
            return $where;
        }

        return sprintf(
                "gec_episode_of_care_id IN (SELECT gap_id_episode FROM gems__appointments WHERE %s)",
                $where
                );
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode): bool
    {
        foreach ($episode->getAppointments() as $appointment) {
            if ($appointment instanceof Appointment) {
                if ($this->matchAppointment($appointment)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *
     * @return boolean When true prefer SQL statements filtering appointments
     */
    public function preferAppointmentSql(): bool
    {
        return true;
    }
}
