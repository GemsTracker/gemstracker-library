<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Agenda\Agenda;
use Gems\Db\ResultFetcher;
use Gems\Menu\RouteHelper;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Gems\Util\Translated;
use Laminas\Filter\ToInt;
use Laminas\Validator\NumberComparison;
use MUtil\Model;
use MUtil\Validator\IsNot;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\AElement;
use Zalt\Model\Dependency\DependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-okt-2014 18:30:05
 */
class AppointmentMaintenanceDependency extends DependencyAbstract
{
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effected items that don't have an effects array
     *
     * @var array
     */
    protected array $_defaultEffects = ['description', 'elementClass', 'label', 'multiOptions',
        'filters', 'validators',
    ];

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overridden in sub class
     *
     * @var array Of name => name
     */
    protected array $_dependentOn = ['gtf_id_track', 'gtf_id_order', 'gtf_filter_id', 'gtf_diff_target_field', 'gtf_max_diff_exists', 'gtf_min_diff_length', 'gtf_create_track'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overridden in subclass
     *
     * @var array of name => array(setting => setting)
     */
    protected array $_effecteds = [
        'gtf_id_order', 'htmlCalc', 'gtf_filter_id', 'gtf_diff_target_field', 'gtf_min_diff_unit', 'gtf_min_diff_length',
        'gtf_max_diff_exists', 'gtf_max_diff_unit', 'gtf_max_diff_length', 'htmlCreate', 'gtf_uniqueness',
        'gtf_create_track', 'gtf_create_wait_days',
    ];

    public function __construct(
        TranslatorInterface $translate,
        protected readonly Agenda $agenda,
        protected readonly Translated $translatedUtil,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly RouteHelper $routeHelper,

    )
    {
        parent::__construct($translate);
    }

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, bool $new = false): array
    {
        // Only change anything when there are filters
        $filters = $this->agenda->getFilterList();

        if (! $filters) {
            return array();
        }

        $output['gtf_id_order'] = [
            'description' => $this->_('The display and processing order of the fields.') . "\n" .
            $this->_('When using automatic filters the fields are ALWAYS filled with appointments in ascending order.'),
        ];

        $output['htmlCalc'] = [
            'label'        => ' ',
            'elementClass' => 'Exhibitor',
        ];
        $output['gtf_filter_id'] = [
            'label'          => $this->_('Appointment filter'),
            'autoSubmit'     => true,
            'description'    => $this->_('Automatically link an appointment when it passes this filter.'),
            'elementClass'   => 'Select',
            'formatFunction' => [$this, 'showFilter', true],
            'multiOptions'   => $this->translatedUtil->getEmptyDropdownArray() + $filters,
        ];

        if ($context['gtf_filter_id']) {
            $periodUnits = $this->translatedUtil->getPeriodUnits();

            $previousAppointmentFieldOptions = [];
            $diffDescription = $this->_('Difference with the previous appointment or track start date, can be negative but not zero');
            if (isset($context['gtf_id_track'], $context['gtf_id_order'])) {
                $previousAppointmentFields = $this->resultFetcher->fetchAll(
                    "SELECT * FROM gems__track_appointments WHERE gtap_id_track = ? AND gtap_id_order < ? ORDER BY gtap_id_order",
                    [$context['gtf_id_track'], $context['gtf_id_order']]
                );
                // \MUtil\EchoOut\EchoOut::track($previous);

                $diffDescription = $this->_('Difference with the track start date, can be negative but not zero');

                if ($previousAppointmentFields) {
                    $diffField = $previousAppointmentFields[array_key_last($previousAppointmentFields)];

                    foreach($previousAppointmentFields as $previousAppointmentField) {
                        $key = FieldsDefinition::makeKey(FieldMaintenanceModel::APPOINTMENTS_NAME, $previousAppointmentField['gtap_id_app_field']);
                        $previousAppointmentFieldOptions[$key] = $previousAppointmentField['gtap_field_name'];

                        if ($context['gtf_diff_target_field'] === $key) {
                            $diffField = $previousAppointmentField;
                        }
                    }

                    if ($context['gtf_diff_target_field'] !== 'start') {
                        $diffDescription = sprintf(
                            $this->_(
                                "Difference with the previous '%s' appointment (order %d), can be negative but not zero"
                            ),
                            $diffField['gtap_field_name'],
                            $diffField['gtap_id_order']
                        );
                    }
                }
            }

            $output['gtf_diff_target_field'] = [
                'label' => $this->_('Difference from'),
                'description' => 'Calculate difference with appointment in specific track field',
                'elementClass' => 'Select',
                'multiOptions' => ['' => $this->_('(Previous or track start)'), 'start' => $this->_('Track start')] + $previousAppointmentFieldOptions ,
                'required' => false,
            ];

            $output['gtf_min_diff_length'] = [
                'label'             => $this->_('Minimal time difference'),
                'description'       => $diffDescription,
                'elementClass'      => 'Text',
                'required'          => true,
                // 'size'              => 5, // Causes trouble during save
                'filters[int]'      => ToInt::class,
                'validators[isnot]' => new IsNot(0, $this->_('This value may not be zero!')),
            ];
            $output['gtf_min_diff_unit'] = [
                'label'        => $this->_('Minimal difference unit'),
                'elementClass' => 'Select',
                'multiOptions' => $periodUnits,
            ];
            $output['gtf_max_diff_exists'] = [
                'label'        => $this->_('Set a maximum time difference'),
                'autoSubmit'   => true,
                'elementClass' => 'Checkbox',
            ];
            if ($context['gtf_max_diff_exists']) {
                $output['gtf_max_diff_length'] = [
                    'label'             => $this->_('Maximum time difference'),
                    'elementClass'      => 'Text',
                    'required'          => false,
                    // 'size'              => 5, // Causes trouble during save
                    'filters[int]'      => ToInt::class,
                ];
                if ($context['gtf_min_diff_length'] < 0) {
                    $output['gtf_max_diff_length']['description'] = $this->_(
                            'Must be negative, just like the minimal difference.'
                            );
                    $output['gtf_max_diff_length']['validators[lt]'] = new NumberComparison(['max' => 0]);
                } else {
                    $output['gtf_max_diff_length']['description'] = $this->_(
                            'Must be positive, just like the minimal difference.'
                            );
                    $output['gtf_max_diff_length']['validators[gt]'] = new NumberComparison(['min' => 0]);
                }
                $output['gtf_max_diff_unit'] = [
                    'label'        => $this->_('Maximum difference unit'),
                    'elementClass' => 'Select',
                    'multiOptions' => $periodUnits,
                ];
            }
//            $output['gtf_after_next'] = array(
//                'label'        => $this->_('Link ascending'),
//                'description'  => $this->_('Automatically linked appointments are added in ascending (or otherwise descending) order; starting with the track start date.'),
//                'elementClass' => 'Checkbox',
//                'multiOptions' => $translated->getYesNo(),
//                );
            $output['gtf_uniqueness'] = [
                'label'        => $this->_('Link unique'),
                'description'  => $this->_('Can one appointment be used in multiple fields?'),
                'elementClass' => 'Radio',
                'multiOptions' => [
                    0 => $this->_('No: repeatedly linked appointments are allowed.'),
                    1 => $this->_('A track instance may link only one field to a specific appointment.'),
                    2 => $this->_('All instances of this track may link only once to a specific appointment.'),
    //                 3 => $this->_('Appointment may not be used in any other track.'),
                ],
            ];
            $output['htmlCreate'] = [
                'label'        => ' ',
                'elementClass' => 'Exhibitor',
            ];
            $output['gtf_create_track'] = $this->agenda->getTrackCreateElement();
        }

        $label = false;
        $description = false;
        if ($context['gtf_create_track']) {
            switch ($context['gtf_create_track']) {
                case 1:
                    $label = $this->_('End date difference');
                    $description = $this->_('Any previous track must be closed and have an end date at least this many days in the past.');
                    break;
                case 2:
                    $label = $this->_('End date difference');
                    $description = $this->_('Any previous track must have an end date at least this many days in the past.');
                    break;
                case 4:
                    $label = $this->_('Start date difference');
                    $description = $this->_('Any previous track must have an start date at least this many days in the past.');
                    break;
                case 5:
                    break;

            }
        }
        if ($label && $description) {
            $output['gtf_create_wait_days'] = [
                'label'        => $label,
                'description'  => $description,
                'elementClass' => 'Text',
            ];
        }

        return $output;
    }

    /**
     * Show filter as link
     *
     * @param string|null $value
     * @param int|null $raw
     * @return string|AElement|null
     */
    public function showFilter(?string $value, ?int $raw): string|AElement|null
    {
        if ($value === null) {
            return null;
        }

        $menuFilterUrl = $this->routeHelper->getRouteUrl('setup.agenda.filter.show', [Model::REQUEST_ID => $raw]);

        if (! $menuFilterUrl) {
            return $value;
        }

        return AElement::a($menuFilterUrl, $value);
    }
}
