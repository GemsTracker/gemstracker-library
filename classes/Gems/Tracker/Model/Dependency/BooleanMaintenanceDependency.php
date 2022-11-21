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

use Gems\Tracker\Field\BooleanField;
use Gems\Tracker\Field\FieldAbstract;
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
class BooleanMaintenanceDependency extends ValuesMaintenanceDependency
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = ['gtf_field_type', 'gtf_field_values', 'gtf_required'];
    
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
        $multi = $this->translatedUtil->getYesNo();
        if (isset($context['gtf_field_values'])) {
            $values = explode(FieldAbstract::FIELD_SEP, $context['gtf_field_values']);
            if (count($multi) === count(BooleanField::$keyValues)) {
                $multi = $values;
            }
        }

        $empty = [];
        if ($context['gtf_required'] != 1) {
            $empty = $this->translatedUtil->getEmptyDropdownArray();
        }

        return [
            'gtf_field_values' => [
                'label'          => $this->_('Values'),
                'description'    => $this->_('Leave empty for Yes|No. Add two values as replacement. Separate multiple values with a vertical bar (|)'),
                'elementClass'   => 'Text',
                'formatFunction' => [$this, 'formatValues'],
                'minlength'      => 3,// At least two single chars and a separator
            ],
            'gtf_field_default' => [
                'label'        => $this->_('Default'),
                'description'  => $this->_('Choose the default value'),
                'elementClass' => 'Select',
                'multiOptions' => $empty + array_combine(BooleanField::$keyValues, array_slice($multi,0,2)),
            ],
        ];
    }
}
