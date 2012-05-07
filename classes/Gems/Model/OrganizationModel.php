<?php
/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Contains the organization
 *
 * Handles saving of the user definition config
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Model_OrganizationModel extends Gems_Model_ModelAbstract
{
    /**
     * @var Gems_Loader
     */
    public $loader;

    public function __construct()
    {
        parent::__construct('organization', 'gems__organizations', 'gor');
    }

    /**
     * Save a single model item.
     *
     * Makes sure the password is saved too using the userclass
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @param array $saveTables Optional array containing the table names to save,
     * otherwise the tables set to save at model level will be saved.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        //First perform a save
        $savedValues = parent::save($newValues, $filter, $saveTables);

        //Now check if we need to save config values
        if (isset($newValues['gor_user_class']) && !empty($newValues['gor_user_class'])) {
            $definition = $this->loader->getUserLoader()->getUserDefinition($newValues['gor_user_class']);

            if ($definition instanceof Gems_User_UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                $savedValues['config'] = $definition->saveConfig($savedValues,$newValues['config']);
                if ($definition->getConfigChanged()>0 && $this->getChanged()<1) {
                    $this->setChanged(1);
                }
            }
        }

        return $savedValues;
    }

    public function loadFirst($filter = true, $sort = true)
    {
        $data = parent::loadFirst($filter, $sort);

        if (isset($data['gor_user_class']) && !empty($data['gor_user_class'])) {
            $definition = $this->loader->getUserLoader()->getUserDefinition($data['gor_user_class']);

            if ($definition instanceof Gems_User_UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                $data['config'] = $definition->loadConfig($data);
            }
        }

        return $data;
    }
}