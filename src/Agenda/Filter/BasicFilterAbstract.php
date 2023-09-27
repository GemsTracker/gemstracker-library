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

use Gems\Agenda\Filter\AppointmentFilterInterface;
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
abstract class BasicFilterAbstract
    implements AppointmentFilterInterface
{
    /**
     * Constant for filters that should always trigger
     */
    const MATCH_ALL_SQL = '1=1';

    /**
     * Constant for filters that should never trigger
     */
    const NO_MATCH_SQL = '1=0';

    public function __construct(
        protected readonly int $id,
        protected readonly string $calculatedName,
        protected readonly int $order,
        protected readonly bool $active,
        protected readonly string|null $manualName,
        protected readonly string|null $text1,
        protected readonly string|null $text2,
        protected readonly string|null $text3,
        protected readonly string|null $text4,
    )
    {
        $this->afterLoad();
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
    { }

    /**
     * The filter id
     *
     * @return int
     */
    public function getFilterId(): int
    {
        return $this->id;
    }

    /**
     * The name of the filter
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->manualName ?? $this->calculatedName;
    }

    public function __sleep(): array
    {
        $dataFields = array_filter(array_keys(get_object_vars($this)), function($propertyName) {
            return str_starts_with($propertyName, '_');
        });

        return [
            'id',
            'calculatedName',
            'order',
            'active',
            'manualName',
            'text1',
            'text2',
            'text3',
            'text4',
            ...$dataFields,
        ];
    }
}
