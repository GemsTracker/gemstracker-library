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
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Delegates authentication to the Radius server
 *
 * Configuration should be done at organization level, and users should still be
 * added and created as usual. There is now way of changing the remote password.
 * It might be possible to show a custom message maybe with a link to the place
 * where people can change their password in the near future but that is not
 * planned for now.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_RadiusUserDefinition extends Gems_User_StaffUserDefinition implements Gems_User_UserDefinitionConfigurableInterface
{
    /**
     * @var Gems_Model_JoinModel
     */
    protected $_configModel;

    /**
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * @var Zend_Translate_Adapter
     */
    protected $translate;

    /**
     * Appends the needed fields for this config to the $bridge
     *
     * @param MUtil_Model_Bridge_FormBridgeInterface $bridge
     */
    public function appendConfigFields(MUtil_Model_Bridge_FormBridgeInterface $bridge)
    {
        $model = $this->getConfigModel();

        $bridge->getTab('access');
        foreach ($model->getItemNames() as $name) {
            if ($label = $model->get($name, 'label')) {
                //We supply all options from the model as the bridge doesn't know about this model
                $element = $bridge->add($name, $model->get($name));
            } else {
                $element = $bridge->addHidden($name);
            }
            $element->setBelongsTo('config');
        }
    }

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(Gems_User_User $user = null)
    {
        return false;
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(Gems_User_User $user = null)
    {
        return false;
    }

    /**
     * Returns an initialized Zend_Auth_Adapter_Interface
     *
     * @param Gems_User_User $user
     * @param string $password
     * @return Zend_Auth_Adapter_Interface
     */
    public function getAuthAdapter(Gems_User_User $user, $password)
    {
        //Ok hardcoded for now this needs to be read from the userdefinition
        $configData = $this->loadConfig(array('gor_id_organization' => $user->getBaseOrganizationId()));

        $config  = array('ip'                 => $configData['grcfg_ip'],
                         'authenticationport' => $configData['grcfg_port'],
                         'sharedsecret'       => $configData['grcfg_secret']);

        //Unset empty
        foreach($config as $key=>$value) {
            if (empty($value)) {
                unset($config[$key]);
            }
        }
        $adapter = new Gems_User_Adapter_Radius($config);

        $adapter->setIdentity($user->getLoginName())
                ->setCredential($password);

        return $adapter;
    }

    /**
     * Return the number of changed records for the save performed
     */
    public function getConfigChanged()
    {
        return $this->getConfigModel()->getChanged();
    }

    /**
     * Get a model to store the config
     *
     * @return Gems_Model_JoinModel
     */
    protected function getConfigModel() {
        if (!$this->_configModel) {
            $model = new Gems_Model_JoinModel('config', 'gems__radius_config', 'grcfg');

            $model->setIfExists('grcfg_ip', 'label', $this->translate->_('IP address'));
            $model->setIfExists('grcfg_port', 'label', $this->translate->_('Port'));
            $model->setIfExists('grcfg_secret',
                'label', $this->translate->_('Shared secret'),
                'elementClass', 'password',
                'required', false,
                'description', $this->translate->_('Enter only when changing'));
            $model->setSaveWhenNotNull('grcfg_secret');
            $this->_configModel = $model;
        }

        return $this->_configModel;
    }

    /**
     * Return a password reset key, never reached as we can not reset the password
     *
     * @param Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(Gems_User_User $user)
    {
        return null;
    }

    /**
     * Copied from Gems_User_StaffUserDefinition but left out the password link
     *
     * @param type $login_name
     * @param type $organization
     * @return Zend_Db_Select
     */
    protected function getUserSelect($login_name, $organization)
    {
        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility accross implementations.
         */
        $select = new Zend_Db_Select($this->db);
        $select->from('gems__user_logins', array(
                    'user_login_id' => 'gul_id_user',
                    ))
                ->join('gems__staff', 'gul_login = gsf_login AND gul_id_organization = gsf_id_organization', array(
                    'user_id'             => 'gsf_id_user',
                    'user_login'          => 'gsf_login',
                    'user_email'          => 'gsf_email',
                    'user_first_name'     => 'gsf_first_name',
                    'user_surname_prefix' => 'gsf_surname_prefix',
                    'user_last_name'      => 'gsf_last_name',
                    'user_gender'         => 'gsf_gender',
                    'user_group'          => 'gsf_id_primary_group',
                    'user_locale'         => 'gsf_iso_lang',
                    'user_logout'         => 'gsf_logout_on_survey',
                    'user_base_org_id'    => 'gsf_id_organization',
                    ))
               ->join('gems__groups', 'gsf_id_primary_group = ggp_id_group', array(
                   'user_role'=>'ggp_role',
                   'user_allowed_ip_ranges' => 'ggp_allowed_ip_ranges',
                   ))
               //->joinLeft('gems__user_passwords', 'gul_id_user = gup_id_user', array(
               //    'user_password_reset' => 'gup_reset_required',
               //    ))
               ->where('ggp_group_active = 1')
               ->where('gsf_active = 1')
               ->where('gul_can_login = 1')
               ->where('gul_login = ?')
               ->where('gul_id_organization = ?')
               ->limit(1);

        return $select;
    }

    /**
     * Do we need to add custom config parameters to use this definition?
     *
     * For now these will be added in the organization dialog as most of the time the config
     * will be organization specific. To be extended when needed
     *
     * @return boolean
     */
    public function hasConfig()
    {
        return true;
    }


    /**
     * Return true if the user has a password.
     *
     * Seems to be only used on changing a password, so will probably never be reached
     *
     * @param Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(Gems_User_User $user)
    {
       return true;
    }

    /**
     * Handles loading the config for the given data
     *
     * @param array $data
     * @return array
     */
    public function loadConfig($data)
    {
        $model = $this->getConfigModel();

        $newData  = $model->loadFirst(array('grcfg_id_organization'=>$data['gor_id_organization']));
        $newData['grcfg_id_organization'] = $data['gor_id_organization'];

        return $newData;
    }

    /**
     * Handles saving the configvalues in $values using the $data
     *
     * @param array $data
     * @param array $values
     * @return array
     */
    public function saveConfig($data, $values)
    {
        $model     = $this->getConfigModel();

        $values['grcfg_id_organization'] = $data['gor_id_organization'];

        return $model->save($values);
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param Gems_User_User $user The user whose password to change
     * @param string $password
     * @return Gems_User_UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(Gems_User_User $user, $password)
    {
        return $this;
    }
}