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
class ValuesMaintenanceDependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gtf_field_type', 'gtf_field_values');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array(
        'gtf_field_values' => array(
            'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'rows', 'required',
            ),
        'gtf_field_default' => array(
            'description', 'elementClass', 'label', 'multiOptions',
            ),
        );

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

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
        $multi = explode(FieldAbstract::FIELD_SEP, $context['gtf_field_values']);

        return array(
            'gtf_field_values' => array(
                'label'          => $this->_('Values'),
                'description'    => $this->_('Separate multiple values with a vertical bar (|)'),
                'elementClass'   => 'Textarea',
                'formatFunction' => array($this, 'formatValues'),
                'minlength'      => 3,// At least two single chars and a separator
                'rows'           => 4,
                'required'       => true,
                ),
            'translations_gtf_field_values' => array(
                'model' => [
                    'gtrs_translation' => [
                            'elementClass'   => 'Textarea',
                            'formatFunction' => array($this, 'formatValues'),
                            'minlength'      => 3,// At least two single chars and a separator
                            'rows'           => 4,
                    ],
                    'gtrs_iso_lang' => [
                        'elementClass' => 'Exhibitor',
                    ],
                ]
            ),
            'gtf_field_default' => array(
                'label'        => $this->_('Default'),
                'description'  => $this->_('Choose the default value'),
                'elementClass' => 'Select',
                'multiOptions' => $this->util->getTranslated()->getEmptyDropdownArray() + array_combine($multi, $multi),
                ),
            );
    }
}
