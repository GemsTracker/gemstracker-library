<?php


namespace Gems\Tracker\Model\Dependency;


use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Field\FieldAbstract;
use Gems\Tracker\Model\FieldMaintenanceModel;
use MUtil\Model\Dependency\DependencyAbstract;

class OtherFieldValuesDependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = ['gtf_field_type', 'gtf_field_values', 'gtf_id_track'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = [
        'htmlCalc' => ['elementClass', 'label'],
        'gtf_calculate_using' => ['description', 'elementClass', 'formatFunction', 'label', 'multiOptions'],
        'gtf_field_values' => [
            'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'rows', 'required',
        ],
        'gtf_field_default' => [
            'description', 'elementClass', 'label', 'multiOptions',
        ],
    ];

    /**
     * The current trackId
     * @var int
     */
    protected $_trackId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @param int $trackId The trackId for this dependency
     */
    public function __construct($trackId)
    {
        $this->_trackId = $trackId;

        parent::__construct();
    }

    /**
     * Use this function for a default application of this dependency to the model
     *
     * @param \MUtil\Model\ModelAbstract $model Try not to store the model as variabe in the dependency (keep it simple)
     */
    public function applyToModel(\MUtil\Model\ModelAbstract $model)
    {
        $model->set('gtf_calculate_using', 'elementClass', 'MultiCheckbox', 'description', null);
    }

    /**
     * This formatFunction is needed because the options are not set before the concatenated row
     *
     * @param string $value
     * @return string
     */
    public function formatValues($value)
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
    public function getChanges(array $context, $new)
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
    protected function getOptions($trackId = null)
    {
        if (null === $trackId) {
            $trackId = $this->_trackId;
        }

        $trackFieldPrefix = FieldMaintenanceModel::FIELDS_NAME . FieldsDefinition::FIELD_KEY_SEPARATOR;
        $trackfieldSelect = $this->db->select();
        $trackfieldSelect->from('gems__track_fields', [])
            ->columns(
                [
                    'field' => new \Zend_Db_Expr('CONCAT(\''.$trackFieldPrefix.'\', gtf_id_field)'),
                    'name' => 'gtf_field_name',
                    'order' => 'gtf_id_order',
                ]
            )
            ->where('gtf_id_track = ?', $trackId);

        $appointmentFieldPrefix = FieldMaintenanceModel::APPOINTMENTS_NAME . FieldsDefinition::FIELD_KEY_SEPARATOR;
        $appointmentFieldSelect = $this->db->select();
        $appointmentFieldSelect->from('gems__track_appointments', [])
            ->columns(
                [
                    'field' => new \Zend_Db_Expr('CONCAT(\''.$appointmentFieldPrefix.'\', gtap_id_app_field)'),
                    'name' => 'gtap_field_name',
                    'order' => 'gtap_id_order',
                ]
            )
            ->where('gtap_id_track = ?', $trackId);

        $select = $this->db->select();
        $select->union([$trackfieldSelect, $appointmentFieldSelect])
            ->order('order');

        $options = $this->db->fetchPairs($select);

        return $options;
    }
}
