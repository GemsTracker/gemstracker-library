<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FromAppointmentsMaintenanceDependency.php $
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;
use MUtil\Model\Dependency\DependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-mrt-2015 14:00:41
 */
class FromAppointmentsMaintenanceDependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gtf_id_track');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array(
        'htmlCalc' => array('elementClass', 'label'),
        'gtf_calculate_using' => array('description', 'elementClass', 'formatFunction', 'label', 'multiOptions')
        );

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
     * @param \MUtil_Model_ModelAbstract $model Try not to store the model as variabe in the dependency (keep it simple)
     */
    public function applyToModel(\MUtil_Model_ModelAbstract $model)
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

        if ($options) {
            // formatFunction is needed because the options are not set before the concatenated row
            return array(
                'htmlCalc' => array(
                    'label'        => ' ',
                    'elementClass' => 'Exhibitor',
                    ),
                'gtf_calculate_using' => array(
                    'label'          => $this->_('Calculate from'),
                    'description'    => $this->_('Automatically calculate this field using other fields'),
                    'elementClass'   => 'MultiCheckbox',
                    'formatFunction' => array($this, 'formatValues'),
                    'multiOptions'   => $this->getOptions($context['gtf_id_track']),
                    ),
                );
        }
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

        $appFields = $this->db->fetchPairs("
            SELECT gtap_id_app_field, gtap_field_name
                FROM gems__track_appointments
                WHERE gtap_id_track = ?
                ORDER BY gtap_id_order", $trackId);

        $options = array();

        if ($appFields) {
            foreach ($appFields as $id => $label) {
                $key = FieldsDefinition::makeKey(FieldMaintenanceModel::APPOINTMENTS_NAME, $id);
                $options[$key] = $label;
            }
        }

        return $options;

    }
}
