<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

use DateTimeImmutable;
use DateTimeInterface;

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Date\Period;
use Gems\Menu\RouteHelper;
use Gems\Model;
use Gems\Tracker;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Html\HtmlElement;
use Zalt\Model\MetaModelInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:43:04
 */
class AppointmentField extends FieldAbstract
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array|null an array of respondent track fields.
     */
    protected array|null $_dependsOn = ['gr2t_id_user', 'gr2t_id_organization'];

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array|null an array of model settings that change for this field
     */
    protected array|null $_effecteds = ['multiOptions'];

    /**
     * The last active appointment in any field
     *
     * Shared among all field instances saving to the same respondent track id
     *
     * @var Appointment[]
     */
    protected static array  $_lastActiveAppointment = [];

    /**
     * The last active appointment in any field
     *
     * Shared among all field instances saving to the same respondent track id
     *
     * @var array of $_lastActiveKey => array(appId => appId)
     */
    protected static array $_lastActiveAppointmentIds = [];

    /**
     * The key for the current calculation to self::$_lastActiveAppointment  and
     * self::$_lastActiveAppointmentIds
     *
     * @var string|int|null
     */
    protected string|int|null $_lastActiveKey;

    /**
     * The format string for outputting appointments
     *
     * @var string
     */
    protected string $appointmentTimeFormat = 'd-m-Y H:i';

    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        TranslatorInterface $translator,
        Translated $translatedUtil,
        protected readonly Agenda $agenda,
        protected readonly RouteHelper $routeHelper,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil);
    }

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $settings['elementClass']   = 'Select';
        $settings['formatFunction'] = [$this, 'showAppointment'];
    }

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed
    {
        if (! $currentValue) {
            return $currentValue;
        }

        $appointment = $this->agenda->getAppointment($currentValue);

        if ($appointment->isActive()) {
            $time = $appointment->getAdmissionTime();

            if ($time) {
                return $time->format($this->appointmentTimeFormat);
            }
        }

        return null;
    }

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other known field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return string|int|null the new value
     */
    public function calculateFieldValue(mixed $currentValue, array $fieldData, array $trackData): string|int|null
    {
        if ($currentValue || isset($this->fieldDefinition['gtf_filter_id'])) {
            if ($this->_lastActiveKey && isset($this->fieldDefinition['gtf_filter_id'])) {
                $fromDate = $this->getFromDate($trackData, $fieldData);

                if ($fromDate instanceof DateTimeInterface) {
                    $select = $this->agenda->createAppointmentSelect(['gap_id_appointment']);
                    $select->onlyActive()
                            ->forFilterId($this->fieldDefinition['gtf_filter_id'])
                            ->forRespondent($trackData['gr2t_id_user'], $trackData['gr2t_id_organization']);

                    $minDate = Period::applyPeriod(
                            $fromDate,
                            $this->fieldDefinition['gtf_min_diff_unit'],
                            (int)$this->fieldDefinition['gtf_min_diff_length']
                            );
                    if ($this->fieldDefinition['gtf_max_diff_exists']) {
                        $maxDate = Period::applyPeriod(
                                $fromDate,
                                $this->fieldDefinition['gtf_max_diff_unit'],
                                (int)$this->fieldDefinition['gtf_max_diff_length']
                                );
                    } else {
                        $maxDate = null;
                    }
                    if ($this->fieldDefinition['gtf_min_diff_length'] > 0) {
                        $select->forPeriod($minDate, $maxDate, true);
                    } else {
                        $select->forPeriod($maxDate, $minDate, false);
                    }

                    if ($this->fieldDefinition['gtf_uniqueness']) {
                        switch ($this->fieldDefinition['gtf_uniqueness']) {
                            case 1: // Track instances may link only once to an appointment
                                $select->uniqueInTrackInstance(
                                        self::$_lastActiveAppointmentIds[$this->_lastActiveKey]
                                        );
                                break;

                            case 2: // Tracks of this type may link only once to an appointment
                                $respTrackId = null;
                                if (isset($trackData['gr2t_id_respondent_track'])) {
                                    $respTrackId = $trackData['gr2t_id_respondent_track'];
                                }
                                $select->uniqueForTrackId(
                                        $this->trackId,
                                        $respTrackId,
                                        self::$_lastActiveAppointmentIds[$this->_lastActiveKey]
                                        );
                                break;

                            // default:
                        }
                    }

                    // Query ready
                    // echo "\n" . $select->getSelect()->__toString() . "\n";
                    $newValue = $select->fetchOne();

                    if ($newValue) {
                        $currentValue = $newValue;
                    }
                }
            }

            if ($this->_lastActiveKey && $currentValue) {
                $this->setLastActiveAppointmentFromValue($currentValue);
            }
        }

        return $currentValue;

    }

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return self
     */
    public function calculationStart(array $trackData): FieldAbstract
    {
        $this->_lastActiveKey = null;
        if (isset($trackData['gr2t_id_respondent_track'])) {
            $this->_lastActiveKey = $trackData['gr2t_id_respondent_track'];
        } elseif (isset($trackData['gr2t_id_user'], $trackData['gr2t_id_organization'])) {
            $this->_lastActiveKey = $trackData['gr2t_id_user'] . '__' . $trackData['gr2t_id_organization'];
        }
        if ($this->_lastActiveKey) {
            self::$_lastActiveAppointment[$this->_lastActiveKey]    = null;
            self::$_lastActiveAppointmentIds[$this->_lastActiveKey] = [];
        }

        return $this;
    }

    /**
     * Returns the changes to the model for this field that must be made in an array consisting of
     *
     * <code>
     *  array(setting1 => $value1, setting2 => $value2, ...),
     * </code>
     *
     * By using [] array notation in the setting array key you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array|null (setting => value)
     */
    public function getDataModelDependencyChanges(array $context, bool $new): array|null
    {
        if ($this->isReadOnly()) {
            return null;
        }

        $empty  = $this->translatedUtil->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $this->agenda->getActiveAppointments(
                $context['gr2t_id_user'],
                $context['gr2t_id_organization']
                );

        return $output;
    }

    public function getFromDate(array $trackData, array $fieldData = []): DateTimeInterface|null
    {
        // Default is last appointment
        $targetCheckAppointment = self::$_lastActiveAppointment[$this->_lastActiveKey];

        if ($this->fieldDefinition['gtap_diff_target_field'] !== null) {
            if ($this->fieldDefinition['gtap_diff_target_field'] === 'start') {
                $targetCheckAppointment = null;
            }
            if (isset($fieldData[$this->fieldDefinition['gtap_diff_target_field']])) {
                $targetCheckAppointment = $this->agenda->getAppointment(
                    $fieldData[$this->fieldDefinition['gtap_diff_target_field']]
                );
            }
        }

        if (($targetCheckAppointment instanceof Appointment) && $targetCheckAppointment->isActive()) {
            $fromDate = $targetCheckAppointment->getAdmissionTime();
            return $fromDate->setTime(0,0);
        }

        if (isset($trackData['gr2t_start_date']) && $trackData['gr2t_start_date']) {
            $fromDate = $trackData['gr2t_start_date'];
            if (!$fromDate instanceof DateTimeInterface) {
                $fromDate = DateTimeImmutable::createFromFormat(Tracker::DB_DATETIME_FORMAT, $trackData['gr2t_start_date']);
            }

            // Always use start of the day for start date comparisons
            if ($fromDate instanceof DateTimeImmutable) {
                return $fromDate->setTime(0,0);
            }
        }
        return null;
    }

    public function getLastActiveKey(): string|null
    {
        return $this->_lastActiveKey;
    }

    /**
     *
     * @return boolean When this field can be calculated, but also set manually
     */
    public function hasManualSetOption(): bool
    {
        return (! $this->isReadOnly()) && $this->fieldDefinition['gtf_filter_id'];
    }

    public function setLastActiveAppointmentFromValue(string|int $appointmentId): void
    {
        $appointment = $this->agenda->getAppointment($appointmentId);

        if (!$appointment->isActive()) {
            return;
        }

        self::$_lastActiveAppointment[$this->_lastActiveKey] = $appointment;
        self::$_lastActiveAppointmentIds[$this->_lastActiveKey][$appointmentId] = $appointmentId;
    }

    /**
     * Display an appointment as text
     *
     * @param Appointment|string|int|null $value
     * @return string
     */
    public function showAppointment(Appointment|string|int|null $value): HtmlElement|string
    {
        if (! $value) {
            return $this->translator->_('Unknown');
        }
        if ($value instanceof Appointment) {
            $appointment = $value;
        } else {
            $appointment = $this->agenda->getAppointment($value);
        }

        $url = $this->routeHelper->getRouteUrl('respondent.appointments.show', [
            MetaModelInterface::REQUEST_ID1 => $appointment->getPatientNumber(),
            MetaModelInterface::REQUEST_ID2 => $appointment->getOrganizationId(),
            Model::APPOINTMENT_ID => $appointment->getId(),
        ]);

        if ($url) {
            return Html::create('a', $url, $appointment->getDisplayString());
        }

        return $appointment->getDisplayString();
    }
}
