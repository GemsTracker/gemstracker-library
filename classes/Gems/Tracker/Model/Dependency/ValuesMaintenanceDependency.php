<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ValuesMaintenanceDependency.php $
 */

namespace Gems\Tracker\Model\Dependency;

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
class ValuesMaintenanceDependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gtf_field_type');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array('gtf_field_values' => array(
        'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'rows', 'required',
        ));

    /**
     * Put each value on a separate line
     *
     * @param string $values
     * @return \MUtil_Html_Sequence
     */
    public function formatValues($values)
    {
        return new \MUtil_Html_Sequence(array('glue' => '<br/>'), explode('|', $values));
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
        return array('gtf_field_values' => array(
            'label'          => $this->_('Values'),
            'description'    => $this->_('Separate multiple values with a vertical bar (|)'),
            'elementClass'   => 'Textarea',
            'formatFunction' => array($this, 'formatValues'),
            'minlength'      => 4,
            'rows'           => 4,
            'required'       => true,
            ));
    }
}
