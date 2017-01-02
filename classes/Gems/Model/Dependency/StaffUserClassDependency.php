<?php

/**
 *
 * @package    Gems
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model\Dependency;

use MUtil\Model\Dependency\DependencyAbstract;
/**
 *
 * @package    Gems
 * @subpackage Model\Dependency
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 2, 2017 5:33:40 PM
 */
class StaffUserClassDependency extends DependencyAbstract
{
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effecteds that don't have an effects array
     *
     * @var array
     */
    protected $_defaultEffects = array('multiOptions');

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class, when set to only field names this class will
     * change the array to the correct structure.
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gsf_id_organization');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class, when set to only field names this class will use _defaultEffects
     * to change the array to the correct structure.
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array('gul_user_class');

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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
        if (isset($context['gsf_id_organization'])) {
            $org = $this->loader->getOrganization($context['gsf_id_organization']);

            if ($org instanceof \Gems_User_Organization) {
                return ['gul_user_class' => ['multiOptions' => $org->getAllowedUserClasses()]];
            }
        }
    }
}
