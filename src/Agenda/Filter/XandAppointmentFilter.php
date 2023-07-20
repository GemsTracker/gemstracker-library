<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\Appointment;
use Gems\Agenda\EpisodeOfCare;

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 03-Jun-2020 16:10:43
 */
class XandAppointmentFilter extends AndAppointmentFilter
{
    /**
     * Standard where processing
     *
     * @param string $where
     * @return string
     */
    protected function fixSql(string $where): string
    {
        if ($where == parent::NO_MATCH_SQL) {
            return parent::MATCH_ALL_SQL;
        } elseif ($where == parent::MATCH_ALL_SQL) {
            return parent::NO_MATCH_SQL;
        } else {
            return "NOT ($where)";
        }
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere(): string
    {
        return $this->fixSql(parent::getSqlAppointmentsWhere());
    }

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere(): string
    {
        return $this->fixSql(parent::getSqlEpisodeWhere());
    }

    /**
     * Check a filter for a match
     *
     * @param Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(Appointment $appointment): bool
    {
        return ! parent::matchAppointment($appointment);
    }

    /**
     * Check a filter for a match
     *
     * @param EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode): bool
    {
        return ! parent::matchEpisode($episode);
    }
}
