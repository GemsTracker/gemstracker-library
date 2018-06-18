<?php

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Dependency\Condition;

/**
 *
 * @package    Gems
 * @subpackage Condition
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ClassDependency extends \MUtil\Model\Dependency\DependencyAbstract
{
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effecteds that don't have an effects array
     *
     * @var array
     */
    protected $_defaultEffects = array();

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class, when set to only field names this class will
     * change the array to the correct structure.
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array(
        'gcon_type', 
        'gcon_class', 
        'gcon_condition_text1',
        'gcon_condition_text2',
        'gcon_condition_text3',
        'gcon_condition_text4',
        );

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class, when set to only field names this class will use _defaultEffects
     * to change the array to the correct structure.
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = [
        'condition_help' => ['value'],
        'gcon_condition_text1' => ['label', 'elementClass', 'multiOptions', 'value'],
        'gcon_condition_text2' => ['label', 'elementClass', 'multiOptions', 'value'],
        'gcon_condition_text3' => ['label', 'elementClass', 'multiOptions', 'value'],
        'gcon_condition_text4' => ['label', 'elementClass', 'multiOptions', 'value'],
        ];

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Loader
     */
    protected $util;

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
        $conditions = $this->loader->getConditions();

        if (isset($context['gcon_type'],$context['gcon_class']) && !empty($context['gcon_class'])) {
            $condition = $conditions->loadConditionForType($context['gcon_type'],$context['gcon_class']);
            
            $changes = [
                'condition_help' => ['value' => \MUtil_Html::raw('<pre>' . $condition->getHelp() . '</pre>')],
            ] + $condition->getModelFields($context, $new);
            
            return $changes;
        }
        
        return [];
    }

}
