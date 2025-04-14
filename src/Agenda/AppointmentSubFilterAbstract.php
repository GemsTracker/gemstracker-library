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

use Gems\Agenda\Filter\AppointmentFilterInterface;
use Gems\Agenda\Filter\BasicFilterAbstract;
use Gems\Agenda\Repository\FilterRepository;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 17-okt-2014 14:46:23
 */
abstract class AppointmentSubFilterAbstract extends BasicFilterAbstract
{
    /**
     *
     * @var boolean When true prefer appointments SQL
     */
    protected $_preferAppointments = true;

    /**
     *
     * @var array of AppointmentFilterInterface instances
     */
    protected $_subFilters = [];

    public function __construct(
        int $id,
        string $calculatedName,
        int $order,
        bool $active,
        ?string $manualName,
        ?string $text1,
        ?string $text2,
        ?string $text3,
        ?string $text4,
        protected readonly Agenda $agenda,
        protected readonly FilterRepository $filterRepository,
    ) {
        parent::__construct($id, $calculatedName, $order, $active, $manualName, $text1, $text2, $text3, $text4);
    }

    /**
     * Override this function when you need to perform any actions when the data is loaded.
     *
     * Test for the availability of variables as these objects can be loaded data first after
     * deserialization or registry variables first after normal instantiation.
     *
     * That is why this function called both at the end of afterRegistry() and after exchangeArray(),
     * but NOT after unserialize().
     *
     * After this the object should be ready for serialization
     */
    protected function afterLoad(): void
    {
        if (!$this->_subFilters) {
            // Flexible determination of filters to load. Save for future expansion of number of fields
            $filterIds = array_filter([
                $this->text1,
                $this->text2,
                $this->text3,
                $this->text4,
            ]);

            if ($filterIds) {
                $preferAppBalance = 0;

                foreach($filterIds as $filterId) {
                    if ($this->filterRepository->hasFilterAsSub((int)$filterId, $this->id)) {
                        throw new \RuntimeException(sprintf('Filter (%d) "%s" Cannot be loaded because filter %d already has it as its sub', $this->id, $this->getName(), $filterId));
                    }
                    $filterObject = $this->filterRepository->getFilter($filterId);
                    if ($filterObject instanceof AppointmentFilterInterface) {
                        if ($filterObject->getFilterId() == $filterId) {
                            $this->_subFilters[$filterId] = $filterObject;
                            $preferAppBalance += $filterObject->preferAppointmentSql() ? 1 : -1;
                        }
                    }
                }

                $this->_preferAppointments = $preferAppBalance >= 0;
            }
        }
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @return string
     */
    public function getSqlAppointmentsWhere(): string
    {
        return $this->getSqlWhereBoth(true);
    }

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere(): string
    {
        return $this->getSqlWhereBoth(false);
    }

    /**
     * Generate a where statement to filter the appointment model
     *
     * @param boolean $toApps Whether to return appointment or episode SQL
     * @return string
     */
    abstract public function getSqlWhereBoth(bool $toApps): string;

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems\Agenda\Appointment $appointment
     * @return boolean
     */
    // public function matchAppointment(\Gems\Agenda\Appointment $appointment);

    /**
     *
     * @return boolean When true prefer SQL statements filtering appointments
     */
    public function preferAppointmentSql(): bool
    {
        return $this->_preferAppointments;
    }
}
