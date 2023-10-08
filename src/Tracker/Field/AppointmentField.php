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
     * @var array Null or an array of respondent track fields.
     */
    protected array|null $_dependsOn = ['gr2t_id_user', 'gr2t_id_organization'];

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected array|null $_effecteds = ['multiOptions'];

    /**
     * The last active appointment in any field
     *
     * Shared among all field instances saving to the same respondent track id
     *
     * @var array of \Gems\Agenda\Appointment)
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
     * @var mixed
     */
    protected $_lastActiveKey;

    /**
     * The format string for outputting appointments
     *
     * @var string
     */
    protected string $appointmentTimeFormat = 'j M Y H:i';

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

        if ($appointment && $appointment->isActive()) {
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
     * @return mixed the new value
     */
    public function calculateFieldValue(mixed $currentValue, array $fieldData, array $trackData): mixed
    {
        if ($currentValue || isset($this->fieldDefinition['gtf_filter_id'])) {
            if ($this->_lastActiveKey && isset($this->fieldDefinition['gtf_filter_id'])) {
                $fromDate   = false;
                $lastActive = self::$_lastActiveAppointment[$this->_lastActiveKey];

                if (($lastActive instanceof Appointment) && $lastActive->isActive()) {
                    $fromDate = $lastActive->getAdmissionTime();
                }

                if ((! $fromDate) && isset($trackData['gr2t_start_date']) && $trackData['gr2t_start_date']) {

                    if ($trackData['gr2t_start_date'] instanceof DateTimeInterface) {
                        $fromDate = $trackData['gr2t_start_date'];
                    } else {
                        $fromDate = DateTimeImmutable::createFromFormat(Tracker::DB_DATETIME_FORMAT, $trackData['gr2t_start_date']);
                    }
                    // Always use start of the day for start date comparisons
                    $fromDate->setTime(0,0);
                }

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
                                if (isset($trackData['gr2t_id_respondent_track'])) {
                                    $respTrackId = $trackData['gr2t_id_respondent_track'];
                                } else {
                                    $respTrackId = null;
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
                $appointment = $this->agenda->getAppointment($currentValue);

                if ($appointment->isActive()) {
                    self::$_lastActiveAppointment[$this->_lastActiveKey] = $appointment;
                    self::$_lastActiveAppointmentIds[$this->_lastActiveKey][$currentValue] = $currentValue;
                }
            }
        }

        return $currentValue;

    }

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return \Gems\Tracker\Field\FieldAbstract
     */
    public function calculationStart(array $trackData): FieldAbstract
    {
        if (isset($trackData['gr2t_id_respondent_track'])) {
            $this->_lastActiveKey = $trackData['gr2t_id_respondent_track'];
        } elseif (isset($trackData['gr2t_id_user'], $trackData['gr2t_id_organization'])) {
            $this->_lastActiveKey = $trackData['gr2t_id_user'] . '__' . $trackData['gr2t_id_organization'];
        } else {
            $this->_lastActiveKey = false;
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
     * @return array (setting => value)
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

    /**
     *
     * @return boolean When this field can be calculated, but also set manually
     */
    public function hasManualSetOption(): bool
    {
        return (! $this->isReadOnly()) && $this->fieldDefinition['gtf_filter_id'];
    }

    /**
     * Dispaly an appoitment as text
     *
     * @param mixed $value
     * @return string
     */
    public function showAppointment($value)
    {
        if (! $value) {
            return $this->translator->_('Unknown');
        }
        if ($value instanceof Appointment) {
            $appointment = $value;
        } else {
            $appointment = $this->agenda->getAppointment($value);
        }
        if ($appointment instanceof Appointment) {
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

        return $value;
    }
}
