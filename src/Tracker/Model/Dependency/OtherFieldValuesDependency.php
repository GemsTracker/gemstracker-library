<?php


namespace Gems\Tracker\Model\Dependency;

use Gems\Db\ResultFetcher;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Field\FieldAbstract;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Dependency\DependencyAbstract;
use Zalt\Model\MetaModelInterface;

class OtherFieldValuesDependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected array $_dependentOn = ['gtf_field_type', 'gtf_field_values', 'gtf_id_track'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected array $_effecteds = [
        'htmlCalc' => ['elementClass', 'label'],
        'gtf_calculate_using' => ['description', 'elementClass', 'formatFunction', 'label', 'multiOptions'],
        'gtf_field_values' => [
            'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'rows', 'required',
        ],
        'gtf_field_default' => [
            'description', 'elementClass', 'label', 'multiOptions',
        ],
    ];

    public function __construct(
        protected readonly int $trackId,
        TranslatorInterface $translate,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
        parent::__construct($translate);
    }

    /**
     * Use this function for a default application of this dependency to the model
     */
    public function applyToModel(MetaModelInterface $metaModel)
    {
        $metaModel->set('gtf_calculate_using', 'elementClass', 'MultiCheckbox', 'description', null);
    }

    /**
     * This formatFunction is needed because the options are not set before the concatenated row
     *
     * @param string $value
     * @return string
     */
    public function formatValues(string $value): string
    {
        $options = $this->getOptions();
        if (is_array($value)) {
            if ($options) {
                foreach ($value as &$val) {
                    if (isset($options[$val])) {
                        $val = $options[$val];
                    }
                }
            }
            return implode($this->_('; '), $value);
        }
        if (isset($options[$value])) {
            return $options[$value];
        }
        return $value;
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
        $options = $this->getOptions($context['gtf_id_track']);

        $changes = [];

        if ($options) {
            // formatFunction is needed because the options are not set before the concatenated row
            $changes['htmlCalc'] = [
                'label'        => ' ',
                'elementClass' => 'Exhibitor',
            ];
            $changes['gtf_calculate_using'] = [
                'label'          => $this->_('Calculate from'),
                'description'    => $this->_('Automatically calculate this field using other fields'),
                'elementClass'   => 'MultiCheckbox',
                'formatFunction' => array($this, 'formatValues'),
                'multiOptions'   => $this->getOptions($context['gtf_id_track']),
            ];
        }

        $multi = explode(FieldAbstract::FIELD_SEP, $context['gtf_field_values']);

        return $changes;
    }

    /**
     * Get the calculate from options
     *
     * @param int $trackId
     * @return array
     */
    protected function getOptions(int|null $trackId = null): array
    {
        if (null === $trackId) {
            $trackId = $this->trackId;
        }

        $trackFieldPrefix = FieldMaintenanceModel::FIELDS_NAME . FieldsDefinition::FIELD_KEY_SEPARATOR;
        $trackfieldSelect = $this->resultFetcher->getSelect('gems__track_fields');
        $trackfieldSelect
            ->columns(
                [
                    'field' => new Expression('CONCAT(\''.$trackFieldPrefix.'\', gtf_id_field)'),
                    'name' => 'gtf_field_name',
                    'order' => 'gtf_id_order',
                ]
            )
            ->where(['gtf_id_track' => $trackId]);

        $appointmentFieldPrefix = FieldMaintenanceModel::APPOINTMENTS_NAME . FieldsDefinition::FIELD_KEY_SEPARATOR;
        $appointmentFieldSelect = $this->resultFetcher->getSelect('gems__track_appointments');
        $appointmentFieldSelect
            ->columns(
                [
                    'field' => new Expression('CONCAT(\''.$appointmentFieldPrefix.'\', gtap_id_app_field)'),
                    'name' => 'gtap_field_name',
                    'order' => 'gtap_id_order',
                ]
            )
            ->where(['gtap_id_track' => $trackId]);

        $trackfieldSelect->combine($appointmentFieldSelect)
            ->order('order');

        $options = $this->resultFetcher->fetchPairs($trackfieldSelect);

        return $options;
    }
}
