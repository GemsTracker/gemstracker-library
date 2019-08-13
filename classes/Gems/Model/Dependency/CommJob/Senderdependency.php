<?php

/**
 *
 * @package    Gems
 * @subpackage Model\Dependency\CoomJob
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model\Dependency\CommJob;

use MUtil\Model\Dependency\DependencyAbstract;

/**
 *
 * @package    Gems
 * @subpackage Model\Dependency\CoomJob
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 08-Aug-2019 19:09:20
 */
class Senderdependency extends DependencyAbstract
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class, when set to only field names this class will
     * change the array to the correct structure.
     *
     * @var array Of name => name
     */
    protected $_dependentOn = ['gcj_target', 'gcj_to_method', 'gcj_fallback_method'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class, when set to only field names this class will use _defaultEffects
     * to change the array to the correct structure.
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = ['gcj_to_method', 'gcj_fallback_method', 'gcj_fallback_fixed'];

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
        $output = [];

        $toMethod        = (3 != $context['gcj_target']);
        $fallbackMethod  = ('A' != $context['gcj_to_method']) || (3 == $context['gcj_target']);
        $fallbackAddress = $fallbackMethod && ('F' == $context['gcj_fallback_method']);

        if ($toMethod) {
            $output['gcj_to_method']['elementClass'] = 'Select';
            $output['gcj_to_method']['label']        = $this->_('Addresses used');
        } else {
            $output['gcj_to_method']['elementClass'] = 'Hidden';
            $output['gcj_to_method']['label']        = null;
        }

        if ($fallbackMethod) {
            $output['gcj_fallback_method']['elementClass'] = 'Select';
            if ($toMethod) {
                $output['gcj_fallback_method']['label']    = $this->_('Fallback address used');
            } else {
                $output['gcj_fallback_method']['label']    = $this->_('Staff address used');
            }
        } else {
            $output['gcj_fallback_method']['elementClass'] = 'Hidden';
            $output['gcj_fallback_method']['label']        = null;
        }

        if ($fallbackAddress) {
            $output['gcj_fallback_fixed']['elementClass'] = 'Text';
            $output['gcj_fallback_fixed']['label']        = $this->_('From other');
        } else {
            $output['gcj_fallback_fixed']['elementClass'] = 'Hidden';
            $output['gcj_fallback_fixed']['label']        = null;
        }

        return $output;
    }  // */
}
