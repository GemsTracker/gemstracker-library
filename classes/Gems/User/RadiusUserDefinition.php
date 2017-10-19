<?php
/**
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
class Gems_User_RadiusUserDefinition extends \Gems_User_StaffUserDefinition implements \Gems_User_UserDefinitionConfigurableInterface
{
    /**
     * @var \Gems_Model_JoinModel
     */
    protected $_configModel;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translate;

    /**
     * Appends the needed fields for this config to the $bridge
     *
     * @param \MUtil_Model_ModelAbstract $orgModel
     */
    public function addConfigFields(\MUtil_Model_ModelAbstract $orgModel)
    {
        $configModel = $this->getConfigModel(true);
        $order       = $orgModel->getOrder('gor_user_class') + 1;

        foreach ($configModel->getItemNames() as $name) {
            $orgModel->set($name, 'order', $order++);
            $orgModel->set($name, $configModel->get($name));
        }
    }

    /**
     * Return true if a password reset key can be created.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(\Gems_User_User $user = null)
    {
        return false;
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems_User_User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(\Gems_User_User $user = null)
    {
        return false;
    }

    /**
     * Returns an initialized Zend\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems_User_User $user
     * @param string $password
     * @return Zend\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems_User_User $user, $password)
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
        $adapter = new \Gems_User_Adapter_Radius($config);

        $adapter->setIdentity($user->getLoginName())
                ->setCredential($password);

        return $adapter;
    }

    /**
     * Return the number of changed records for the save performed
     */
    public function getConfigChanged()
    {
        return $this->getConfigModel(true)->getChanged();
    }

    /**
     * Get a model to store the config
     *
     * @param boolean $valueMask MAsk the password or if false decrypt it
     * @return \Gems_Model_JoinModel
     */
    protected function getConfigModel($valueMask = true)
    {
        if (!$this->_configModel) {
            $model = new \MUtil_Model_TableModel('gems__radius_config', 'config');
            // $model = new \Gems_Model_JoinModel('config', 'gems__radius_config', 'grcfg');

            $model->setIfExists('grcfg_ip', 'label', $this->translate->_('IP address'), 'required', true);
            $model->setIfExists('grcfg_port', 'label', $this->translate->_('Port'), 'required', true);
            $model->setIfExists('grcfg_secret',
                    'label', $this->translate->_('Shared secret'),
                    'description', $this->translate->_('Enter only when changing'),
                    'elementClass', 'password',
                    'required', false,
                    'repeatLabel', $this->translate->_('Repeat password')
                    );

            $type = new \Gems_Model_Type_EncryptedField($this->project, $valueMask);
            $type->apply($model, 'grcfg_secret', 'grcfg_encryption');

            $this->_configModel = $model;
        }

        return $this->_configModel;
    }

    /**
     * Return a password reset key, never reached as we can not reset the password
     *
     * @param \Gems_User_User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems_User_User $user)
    {
        return null;
    }

    /**
     * Copied from \Gems_User_StaffUserDefinition but left out the password link
     *
     * @param type $login_name
     * @param type $organization
     * @return \Zend_Db_Select
     */
    protected function getUserSelect($login_name, $organization)
    {
        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility accross implementations.
         */
        $select = new \Zend_Db_Select($this->db);
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
     * @param \Gems_User_User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems_User_User $user)
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
        $model = $this->getConfigModel(false);

        $newData = $model->loadFirst(array('grcfg_id_organization' => $data['gor_id_organization']));
        if (empty($newData)) {
            $newData = $model->loadNew();
        }
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
        $model = $this->getConfigModel(true);

        $values['grcfg_id_organization'] = $data['gor_id_organization'];

        return $model->save($values) + $data;
    }

    /**
     * Set the password, if allowed for this user type.
     *
     * @param \Gems_User_User $user The user whose password to change
     * @param string $password
     * @return \Gems_User_UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(\Gems_User_User $user, $password)
    {
        return $this;
    }
}