<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Dependency;

use MUtil\Model\Dependency\DependencyAbstract;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Model\Dependency
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 May 18, 2017 3:55:29 PM
 */
class DefaultTextDependency extends DependencyAbstract
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
    protected $_effecteds = array(
        'gtf_field_default' => array(
            'description', 'elementClass', 'label',
            ),
        );

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
        return array(
            'gtf_field_default' => array(
                'label'        => $this->_('Default'),
                'description'  => $this->_('Enter a default value'),
                'elementClass' => 'Text',
                ),
            );
    }
}
