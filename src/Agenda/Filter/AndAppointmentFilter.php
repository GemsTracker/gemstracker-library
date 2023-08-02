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
use Gems\Agenda\AppointmentSubFilterAbstract;
use Gems\Agenda\EpisodeOfCare;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-okt-2014 16:56:07
 */
class AndAppointmentFilter extends AppointmentSubFilterAbstract
{
    protected string $glue = ' AND ';

    /**
     * Generate a where statement to filter the appointment model
     *
     * @param boolean $toApps Whether to return appointment or episode SQL
     * @return string
     */
    public function getSqlWhereBoth(bool $toApps): string
    {
        $appWheres = [];
        $epiWheres = [];

        foreach ($this->_subFilters as $filterObject) {
            if ($filterObject instanceof AppointmentFilterInterface) {
                $toApp = $filterObject->preferAppointmentSql();

                if ($toApp) {
                    $where = $filterObject->getSqlAppointmentsWhere();
                } else {
                    $where = $filterObject->getSqlEpisodeWhere();
                }
                if ($where == parent::NO_MATCH_SQL) {
                    return parent::NO_MATCH_SQL;
                } elseif ($where !== parent::MATCH_ALL_SQL) {
                    if ($toApp) {
                        $appWheres[] = $where;
                    } else {
                        $epiWheres[] = $where;
                    }
                }
            }
        }

        if ($toApps) {
            $wheres = $appWheres;

            if ($epiWheres) {
                $wheres[] = sprintf(
                        "gap_id_episode IN (SELECT gec_episode_of_care_id FROM gems__episodes_of_care WHERE %s)",
                        implode($this->glue, $epiWheres)
                        );
            }
        } else {
            $wheres = $epiWheres;

            if ($appWheres) {
                $wheres[] = sprintf(
                        "gec_episode_of_care_id IN (SELECT gap_id_episode FROM gems__appointments WHERE %s)",
                        implode($this->glue, $appWheres)
                        );
            }
        }

        if ($wheres) {
            if (1 == count($wheres)) {
                return reset($wheres);
            }
            return '(' . implode($this->glue, $wheres) . ')';
        } else {
            return parent::MATCH_ALL_SQL;
        }
    }

    /**
     * Check a filter for a match
     *
     * @param Appointment $appointment
     * @return boolean
     */
    public function matchAppointment(Appointment $appointment): bool
    {
        foreach ($this->_subFilters as $filterObject) {
            if ($filterObject instanceof AppointmentFilterInterface) {
                if (! $filterObject->matchAppointment($appointment)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode): bool
    {
        foreach ($this->_subFilters as $filterObject) {
            if ($filterObject instanceof AppointmentFilterInterface) {
                if (! $filterObject->matchEpisode($episode)) {
                    return false;
                }
            }
        }
        return true;
    }
}
