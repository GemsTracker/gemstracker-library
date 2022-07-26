<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Tracker\Model\FieldMaintenanceModel;
use MUtil\Model\Dependency\DependencyAbstract;

/**
 * Class that checks whether changing the field type is allowed.
 *
 * @subpackage Tracker_Model
 * @subpackage FieldTypeChangeDependency
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-mrt-2015 13:07:12
 */
class FieldTypeChangeableDependency extends DependencyAbstract
{
    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array('gtf_field_type' => array('elementClass', 'onchange'));

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @param string $dependsOn the model field to depend on
     */
    public function __construct($dependsOn)
    {
        $this->_dependentOn = array($dependsOn);

        parent::__construct();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        // Loaded from tracker and tracker does not always have the request as source value
        if (! $this->request instanceof \Zend_Controller_Request_Abstract) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
        }
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
        $subChange = true;

        if (! $new) {
            $fieldName = reset($this->_dependentOn);

            if (isset($context[$fieldName])) {
                $sql = $this->getSql($context[$fieldName]);
                $fid = $this->request->getParam(\Gems\Model::FIELD_ID);

                if ($sql && $fid) {
                    $subChange = ! $this->db->fetchOne($sql, $fid);
                }
            }
        }

        if ($subChange) {
            return array('gtf_field_type' => array(
                'elementClass' => 'Select',
                'onchange'     => 'this.form.submit();',
                ));
        }
    }

    /**
     * Adapt/extend this function if you need different queries
     * for other types
     *
     * @param string $subId The current sub type of field
     * @return string|boolean An sql statement or false
     */
    protected function getSql($subId)
    {
        if ($subId == FieldMaintenanceModel::FIELDS_NAME) {
            return "SELECT gr2t2f_id_field
                FROM gems__respondent2track2field
                WHERE gr2t2f_id_field = ?";
        }

        if ($subId == FieldMaintenanceModel::APPOINTMENTS_NAME) {
            return "SELECT gr2t2a_id_app_field
                FROM gems__respondent2track2appointment
                WHERE gr2t2a_id_app_field = ?";
        }

        return false;
    }
}
