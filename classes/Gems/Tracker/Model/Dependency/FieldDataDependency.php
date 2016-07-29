<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldDataDependency.php $
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Tracker\Field\FieldInterface;
use MUtil\Model\Dependency\DependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 9-mrt-2015 17:53:07
 */
class FieldDataDependency extends DependencyAbstract
{
    /**
     *
     * @var array of \Gems\Tracker\Field\FieldInterface
     */
    protected $_fields;

    /**
     * Add a field to this dependency
     *
     * @param FieldInterface $field
     * @return \Gems\Tracker\Model\FieldDataDependency
     */
    public function addField(FieldInterface $field)
    {
        $key = $field->getFieldKey();

        $this->addDependsOn($field->getDataModelDependsOn());
        $this->addEffected($key, $field->getDataModelEffecteds());

        $this->_fields[$key] = $field;

        return $this;
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
        $output = array();

        foreach ($this->_fields as $key => $field) {
            if ($field instanceof FieldInterface) {
                $changes = $field->getDataModelDependyChanges($context, $new);

                if ($changes) {
                    $output[$field->getFieldKey()] = $changes;
                }
            }
        }

        return $output;
    }

    /**
     * Add a field to this dependency
     *
     * @param FieldInterface $field
     * @return \Gems\Tracker\Model\FieldDataDependency
     */
    public function getFieldCount()
    {
        return count($this->_fields);
    }
}
