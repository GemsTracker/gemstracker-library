<?php
/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
interface Gems_User_UserDefinitionConfigurableInterface
{
    /**
     * Appends the needed fields for this config to the $bridge
     *
     * @param \MUtil_Model_ModelAbstract $orgModel
     */
    public function addConfigFields(\MUtil_Model_ModelAbstract $orgModel);

    /**
     * Should return the number of changed records for the save performed
     */
    public function getConfigChanged();

    /**
     * Do we need to add custom config parameters to use this definition?
     *
     * @return boolean
     */
    public function hasConfig();

    /**
     * Handles loading the config for the given data
     *
     * @param array $data
     * @return array
     */
    public function loadConfig($data);

    /**
     * Handles saving the configvalues in $values using the $data
     *
     * @param array $data
     * @param array $values
     * @return array
     */
    public function saveConfig($data, $values);
}