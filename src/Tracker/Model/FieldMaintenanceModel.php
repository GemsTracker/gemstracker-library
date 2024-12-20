<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Agenda\Agenda;
use Gems\Db\ResultFetcher;
use Gems\Event\Application\TrackFieldDependencyListEvent;
use Gems\Event\Application\TrackFieldsListEvent;
use Gems\Html;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Laminas\Validator\GreaterThan;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Ra\UnionModel;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class FieldMaintenanceModel extends UnionModel
{
    use TranslateableTrait;

    /**
     * Constant name to id appointment items
     */
    const APPOINTMENTS_NAME = 'a';

    /**
     * Constant name to id field items
     */
    const FIELDS_NAME = 'f';

    /**
     * Option seperator for fields
     */
    const FIELD_SEP = '|';

    /**
     * The field types that have a dependency
     *
     * @var array fieldType => dependency class name (without path elements)
     */
    protected array $dependencies = [
        'activity'          => 'FromAppointmentsMaintenanceDependency',
        'appointment'       => 'AppointmentMaintenanceDependency',
        'appointmentInfo'   => 'ValuesAsReferenceDependency',
        'boolean'           => 'BooleanMaintenanceDependency',
        'caretaker'         => 'FromAppointmentsMaintenanceDependency',
        'date'              => 'FromAppointmentsMaintenanceDependency',
        'dateTime'          => 'FromAppointmentsMaintenanceDependency',
        'location'          => 'FromAppointmentsMaintenanceDependency',
        'multiselect'       => 'ValuesMaintenanceDependency',
        'select'            => 'ValuesMaintenanceDependency',
        'text'              => 'DefaultTextDependency',
        'textarea'          => 'DefaultTextDependency',
        'procedure'         => 'FromAppointmentsMaintenanceDependency',
    ];

    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        TranslatorInterface $translator,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly Translated $translatedUtil,
        protected readonly Agenda $agenda,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly Tracker $tracker,
        protected readonly ResultFetcher $resultFetcher,
        protected string $modelName = 'fields_maintenance',
        protected string $modelField = 'sub',
    ) {
        $event = new TrackFieldDependencyListEvent($this->dependencies);
        $eventDispatcher->dispatch($event, TrackFieldDependencyListEvent::class);
        $this->dependencies = $event->getList();


        $metaModel = new FieldMaintenanceMetaModel($modelName, $metaModelLoader, $tracker, $this->modelField, $this->dependencies);
        $this->translate = $translator;
        parent::__construct($metaModel, $modelField);

        $model = $metaModelLoader->createTableModel('gems__track_fields');
        $model->addColumn(new Expression('gtf_field_values'), 'gtf_field_value_keys');
        $metaModelLoader->setChangeFields($model->getMetaModel(), 'gtf');
        $this->addUnionModel($model, null, self::FIELDS_NAME);

        $this->addAppointmentsToModel();

        $this->metaModel->setKeys([
            Model::FIELD_ID => 'gtf_id_field',
            Model::REQUEST_ID => 'gtf_id_track',
            $this->modelField => 'sub',
        ]);
        $this->setClearableKeys([Model::FIELD_ID => 'gtf_id_field']);
        $this->setSort(['gtf_id_order' => SORT_ASC]);
    }

    public function getMaps(): array
    {
        return [
            Model::FIELD_ID => 'gtf_id_field',
            Model::REQUEST_ID => 'gtf_id_track',
        ];
    }

    /**
     * Add appointment model to union model
     */
    protected function addAppointmentsToModel(): void
    {
        $model = $this->metaModelLoader->createTableModel('gems__track_appointments');
        $this->metaModelLoader->setChangeFields($model->getMetaModel(), 'gtap');

        $map = $model->getMetaModel()->getItemsOrdered();
        $map = array_combine($map, str_replace('gtap_', 'gtf_', $map));
        $map['gtap_id_app_field'] = 'gtf_id_field';

        $this->addUnionModel($model, $map, self::APPOINTMENTS_NAME);

        $model->addColumn(new Expression("'appointment'"), 'gtf_field_type');
        $model->addColumn(new Expression("NULL"), 'gtf_field_values');
        $model->addColumn(new Expression("NULL"), 'gtf_field_value_keys');
        $model->addColumn(new Expression("NULL"), 'gtf_field_default');
        $model->addColumn(new Expression("NULL"), 'gtf_calculate_using');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @param bool $detailed For detailed settings
     * @return self
     */
    public function applyBrowseSettings(bool $detailed = false): self
    {
        $this->metaModel->resetOrder();

        $yesNo = $this->translatedUtil->getYesNo();
        $types = $this->translatedUtil->getEmptyDropdownArray() + $this->getFieldTypes();

        $this->metaModel->set('gtf_id_track'); // Set order
        $this->metaModel->set('gtf_field_name', [
            'label' => $this->_('Name'),
            'translate' => true,
        ]);
        $this->metaModel->set('gtf_id_order', [
            'label' => $this->_('Order'),
            'description' => $this->_('The display and processing order of the fields.'),
        ]);
        $this->metaModel->set('gtf_field_type', [
            'label' => $this->_('Type'),
            'multiOptions' => $types
        ]);
        if ($detailed) { // Set order
            $this->metaModel->set('gtf_field_value_keys'); // Set order
        }
        $this->metaModel->set('gtf_field_values', [
            'translate' => true,
        ]);
        if ($detailed) {
            $this->metaModel->set('gtf_field_default'); // Set order
        }

        $this->metaModel->set('gtf_field_description', [
            'translate' => true,
        ]);
        $this->metaModel->set('gtf_field_code', [
            'label' => $this->_('Field code'),
            'description' => $this->_('Optional code name to link the field to program code.')
        ]);

        $this->metaModel->set('htmlUse', [
            'elementClass' => 'Exhibitor',
            'nohidden' => true,
            'value' => Html::create('h3', $this->_('Field use'))
        ]);
        $this->metaModel->set('gtf_to_track_info', [
            'label' => $this->_('In description'),
            'description' => $this->_('Add this field to the track description'),
            'multiOptions' => $yesNo
        ]);
        $this->metaModel->set('gtf_track_info_label', [
            // No label, set order
            'description' => $this->_('Add the name of this field to the track description'),
            'multiOptions' => $yesNo,
            'required' => false
        ]);
        $this->metaModel->set('gtf_required', [
            'label' => $this->_('Required'),
            'multiOptions' => $yesNo,
            'required' => false
        ]);
        $this->metaModel->set('gtf_readonly', [
            'label' => $this->_('Readonly'),
            'description' => $this->_('Check this box if this field is always set by code instead of the user.'),
            'multiOptions' => $yesNo,
            'required' => false
        ]);

        $this->metaModel->set('htmlCalc', [
            'elementClass' => 'None',
            'nohidden' => true,
            'value' => Html::create('h3', $this->_('Field calculation'))
        ]);

        $this->metaModel->set('gtf_calculate_using', [
            'description' => $this->_('Automatically calculate this field using other fields')
        ]);

        if ($detailed) {
            // Appointment caculcation field
            $this->metaModel->set('gtf_filter_id'); // Set order
            $this->metaModel->set('gtf_min_diff_length'); // Set order
            $this->metaModel->set('gtf_min_diff_unit'); // Set order
            $this->metaModel->set('gtf_max_diff_exists', ['multiOptions' => $yesNo]); // Set order
            $this->metaModel->set('gtf_max_diff_length'); // Set order
            $this->metaModel->set('gtf_max_diff_unit'); // Set order
            $this->metaModel->set('gtf_after_next'); // Set order
            $this->metaModel->set('gtf_uniqueness'); // Set order
        } else {
            $this->metaModel->set('calculation', [
                'label' => $this->_('Calculate using'),
                'description' => $this->_('Automatically calculate this field using other fields'),
                'noSort' => true
            ]);
            $this->metaModel->setOnLoad('calculation', [$this, 'loadCalculationSources']);
        }

        $this->metaModel->set('htmlCreate', [
            'elementClass' => 'None',
            'nohidden' => true,
            'value' => Html::create('h3', $this->_('Automatic track creation'))
        ]);

        $this->metaModel->set('gtf_create_track', [
            'label' => $this->_('When not assigned'),
            'description' => $this->_('Create a track if the respondent does not have a track where this field is empty.'),
            'multiOptions' => $this->agenda->getTrackCreateOptions(),
        ]);
        $this->metaModel->set('gtf_create_wait_days'); // Set order

        if (! $detailed) {
            $this->metaModelLoader->addDatabaseTranslations($this->metaModel, false);
        }

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return self
     */
    public function applyDetailSettings($editing = false): self
    {
        $this->applyBrowseSettings(true);

        if ($this->metaModel instanceof FieldMaintenanceMetaModel) {
            $this->metaModel->addLoadDependency = true;
        }

        $this->metaModel->set('gtf_id_track',  [
            'label' => $this->_('Track'),
            'multiOptions' => $this->trackDataRepository->getAllTracks(),
        ]);
        $this->metaModel->set('gtf_field_description', [
            'label' => $this->_('Description'),
            'description' => $this->_('Optional extra description to show the user.')
        ]);
        $this->metaModel->set('gtf_track_info_label', [
            'label' => $this->_('Add name to description')
        ]);

        $this->metaModel->set('htmlUse', [
            'label' => ' '
        ]);

        // But do always transform gtf_calculate_using on load and save
        // as otherwise we might not be sure what to do
        $this->metaModel->set('gtf_calculate_using', [
           'type' => new ConcatenatedType(self::FIELD_SEP, '; ', false),
        ]);

        $this->metaModel->set('header_creation', [
            'elementClass' => 'None',
        ]);

        // Clean up data always show in browse view, but not always in detail views
        $this->metaModel->set('gtf_create_track', [
            'label' => null
        ]);

        $switches = [
            0 => [
                'gtf_track_info_label'     => ['elementClass' => 'Hidden', 'label' => null],
            ],
        ];
        $this->metaModel->addDependency(['ValueSwitchDependency', $switches], 'gtf_to_track_info');

        if (! $editing) {
            $this->metaModelLoader->addDatabaseTranslations($this->metaModel, true);
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return self
     */
    public function applyEditSettings(): self
    {
        $this->applyDetailSettings(true);

        $this->metaModel->set('gtf_id_field', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_id_track', [
            'elementClass' => 'Exhibitor'
        ]);
        $this->metaModel->set('gtf_field_type', [
            'elementClass' => 'Exhibitor'
        ]);

        $this->metaModel->set('gtf_field_name', [
            'elementClass' => 'Text',
            'size' => '30',
            'minlength' => 2,
            'required' => true,
            'validator' => $this->createUniqueValidator('gtf_field_name', ['gtf_id_track', 'gtf_id_field']),
        ]);

        $this->metaModel->set('gtf_id_order', [
            'elementClass' => 'Text',
            'validators[int]' => 'Digits',
            'validators[gt]' => new GreaterThan(0),
            'validators[unique]' => $this->createUniqueValidator('gtf_id_order', ['gtf_id_track', 'gtf_id_field']),
        ]);

        $this->metaModel->set('gtf_field_code', [
            'elementClass' => 'Text',
            'minlength' => 4
        ]);
        $this->metaModel->set('gtf_field_description', [
            'elementClass' => 'Text',
            'size' => 30,
        ]);
        $this->metaModel->set('gtf_field_values', [
            'elementClass' => 'Hidden',
        ]);
        $this->metaModel->set('gtf_field_default', [
            'elementClass' => 'Hidden'
        ]);

        $this->metaModel->set('gtf_to_track_info', [
            'elementClass' => 'Checkbox',
            'autoSubmit' => true
        ]);
        $this->metaModel->set('gtf_track_info_label', [
            'elementClass' => 'Checkbox',
            'required' => false
        ]);
        $this->metaModel->set('gtf_required', [
            'elementClass' => 'Checkbox'
        ]);
        $this->metaModel->set('gtf_readonly', [
            'elementClass' => 'Checkbox'
        ]);

        $this->metaModel->set('gtf_filter_id', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_min_diff_length', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_min_diff_unit', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_max_diff_length', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_max_diff_unit', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_after_next', [
            'elementClass' => 'None'
        ]);
        $this->metaModel->set('gtf_uniqueness', [
            'elementClass' => 'Hidden'
        ]);

        $this->metaModel->set('gtf_create_track', [
            'elementClass' => 'Hidden'
        ]);
        $this->metaModel->set('gtf_create_wait_days', [
            'elementClass' => 'Hidden'
        ]);

        $class      = 'Model\\Dependency\\FieldTypeChangeableDependency';
        $dependency = $this->tracker->createTrackClass($class, $this->modelField);
        $this->metaModel->addDependency($dependency);

        $this->metaModelLoader->addDatabaseTranslations($this->metaModel, true);

        return $this;
    }

    protected function createUniqueValidator($options, $with = null): ModelUniqueValidator
    {
        $validator = new ModelUniqueValidator($options, $with);
        $validator->setDataModel($this);
        return $validator;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = null): int
    {
        $rows = $this->load($filter);

        foreach ($rows as $row) {
            $name  = $this->getModelNameForRow($row);
            $field = $row['gtf_id_field'];

            if (self::FIELDS_NAME === $name) {
                $this->resultFetcher->deleteFromTable(
                        'gems__respondent2track2field',
                        ['gr2t2f_id_field' => $field]
                        );

            } elseif (self::APPOINTMENTS_NAME === $name) {
                $this->resultFetcher->deleteFromTable(
                        'gems__respondent2track2appointment',
                        ['gr2t2a_id_app_field' => $field]
                );
            }
        }

        return parent::delete($filter);
    }

    /**
     * Get the type from the row in case it was not set
     *
     * @param array $row Loaded row
     * @return string Data type for the row
     */
    protected function getFieldType(array &$row): string
    {
        if (isset($row[$this->modelField]) && ($row[$this->modelField] === self::APPOINTMENTS_NAME)) {
            return 'appointment';
        }

        if (isset($row['gtf_id_field']) && $row['gtf_id_field']) {
            $row[Model::FIELD_ID] = $row['gtf_id_field'];
        }

        if (isset($row[Model::FIELD_ID])) {
            return $this->resultFetcher->fetchOne(
                    "SELECT gtf_field_type FROM gems__track_fields WHERE gtf_id_field = ?",
                    $row[Model::FIELD_ID]
                    );
        }

        if (! $this->metaModel->has('gtf_field_type', 'default')) {
            $this->metaModel->set('gtf_field_type', ['default' => 'text']);
        }
        return $this->metaModel->get('gtf_field_type', 'default');
    }

    /**
     * The list of field types
     *
     * @return array of storage name => label
     */
    public function getFieldTypes(): array
    {
        static $output;

        if (! isset($output)) {
            $output = [
                'activity' => $this->_('Activity'),
                'appointment' => $this->_('Appointment'),
                'appointmentInfo' => $this->_('Appointment info'),
                'boolean' => $this->_('Boolean'),
                'caretaker' => $this->_('Caretaker'),
                'consent' => $this->_('Consent'),
                'date' => $this->_('Date'),
                'text' => $this->_('Free text'),
                'textarea' => $this->_('Long free text'),
                'location' => $this->_('Location'),
                'dateTime' => $this->_('Moment in time'),
                'procedure' => $this->_('Procedure'),
                'relatedTracks' => $this->_('Related tracks'),
                'relation' => $this->_('Relation'),
                'select' => $this->_('Select one'),
                'multiselect' => $this->_('Select multiple'),
                'track' => $this->_('Track'),
            ];

            $event = new TrackFieldsListEvent($output);
            $this->eventDispatcher->dispatch($event, TrackFieldsListEvent::class);
            $output = $event->getList();

            asort($output);
        }
        return $output;
    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * @param array $row
     * @return string
     */
    public function getModelNameForRow(array $row): string
    {
        if (isset($row['gtf_field_type']) && ('appointment' === $row['gtf_field_type'])) {
            return self::APPOINTMENTS_NAME;
        }
        if ((! isset($row['gtf_field_type'])) && isset($row[$this->modelField]) && $row[$this->modelField]) {
            return $row[$this->modelField];
        }
        return self::FIELDS_NAME;
    }

    /**
     * Does the model have a dependencies?
     *
     * @return bool
     */
    public function hasDependencies(): bool
    {
        if ($this->metaModel instanceof FieldMaintenanceMetaModel) {
            return $this->metaModel->addLoadDependency || $this->metaModel->hasDependencies();
        }
        return $this->metaModel->hasDependencies();
    }

    /**
     * A ModelAbstract->setOnLoad() function that concatenates the
     * value if it is an array.
     *
     * @param mixed $value The value being saved
     * @param bool $isNew True when a new item is being saved
     * @param string|null $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param bool $isPost True when passing on post data
     * @return string Description
     */
    public function loadCalculationSources(mixed $value, bool $isNew = false, string|null $name = null, array $context = [], bool $isPost = false): string|null
    {
        if ($isPost) {
            return $value;
        }

        if (isset($context['gtf_filter_id']) && $context['gtf_filter_id']) {
            $filters = $this->agenda->getFilterList();
            return $filters[$context['gtf_filter_id']] ?? sprintf(
                $this->_("Non-existing filter %s"),
                $context['gtf_filter_id']
            );
        }

        if (isset($context['gtf_calculate_using']) && $context['gtf_calculate_using']) {
            $count = substr_count($context['gtf_calculate_using'], '|') + 1;
            return sprintf($this->plural('%d field', '%d fields', $count), $count);
        }

        return $value;
    }

    /**
     * Returns an array containing the first requested item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @param boolean $loadDependencies When true the row dependencies are loaded
     * @return array An array or false
     */
    public function loadFirst($filter = null, $sort = null, $columns = null, bool $loadDependencies = true): array
    {
        // Needed as the default order otherwise triggers the type dependency
        if ($this->metaModel instanceof FieldMaintenanceMetaModel) {
            $oldDep = $this->metaModel->addLoadDependency;
            $this->metaModel->addLoadDependency = $loadDependencies;

            $output = parent::loadFirst($filter, $sort, $columns);

            $this->metaModel->addLoadDependency = $oldDep;
        } else {
            $output = parent::loadFirst($filter, $sort, $columns);
        }

        return $output;
    }
}
