<?php
/**
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\User;

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
class RadiusUserDefinition extends \Gems\User\StaffUserDefinition implements \Gems\User\UserDefinitionConfigurableInterface
{
    /**
     * @var \Gems\Model\JoinModel
     */
    protected $_configModel;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translate;

    /**
     * Appends the needed fields for this config to the $bridge
     *
     * @param \MUtil\Model\ModelAbstract $orgModel
     */
    public function addConfigFields(\MUtil\Model\ModelAbstract $orgModel)
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
     * @param \Gems\User\User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canResetPassword(\Gems\User\User $user = null)
    {
        return false;
    }

    /**
     * Return true if the password can be set.
     *
     * Returns the setting for the definition whan no user is passed, otherwise
     * returns the answer for this specific user.
     *
     * @param \Gems\User\User $user Optional, the user whose password might change
     * @return boolean
     */
    public function canSetPassword(\Gems\User\User $user = null)
    {
        return false;
    }

    /**
     * We never need a rehash
     *
     * @param \Gems\User\User $user
     * @param type $password
     * @return boolean
     */
    public function checkRehash(\Gems\User\User $user, $password)
    {
        return false;
    }

    /**
     * Returns an initialized \Laminas\Authentication\Adapter\AdapterInterface
     *
     * @param \Gems\User\User $user
     * @param string $password
     * @return \Laminas\Authentication\Adapter\AdapterInterface
     */
    public function getAuthAdapter(\Gems\User\User $user, $password)
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
        $adapter = new \Gems\User\Adapter\Radius($config);

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
     * @return \Gems\Model\JoinModel
     */
    protected function getConfigModel($valueMask = true)
    {
        if (!$this->_configModel) {
            $model = new \MUtil\Model\TableModel('gems__radius_config', 'config');
            // $model = new \Gems\Model\JoinModel('config', 'gems__radius_config', 'grcfg');

            $model->setIfExists('grcfg_ip', 'label', $this->translate->_('IP address'), 'required', true);
            $model->setIfExists('grcfg_port', 'label', $this->translate->_('Port'), 'required', true);
            $model->setIfExists('grcfg_secret',
                    'label', $this->translate->_('Shared secret'),
                    'description', $this->translate->_('Enter only when changing'),
                    'elementClass', 'password',
                    'required', false,
                    'repeatLabel', $this->translate->_('Repeat password')
                    );

            $type = new \Gems\Model\Type\EncryptedField($this->project, $valueMask);
            $type->apply($model, 'grcfg_secret');

            $this->_configModel = $model;
        }

        return $this->_configModel;
    }

    /**
     * Return a password reset key, never reached as we can not reset the password
     *
     * @param \Gems\User\User $user The user to create a key for.
     * @return string
     */
    public function getPasswordResetKey(\Gems\User\User $user)
    {
        return null;
    }

    /**
     * Copied from \Gems\User\StaffUserDefinition but left out the password link
     *
     * @param type $login_name
     * @param type $organization
     * @return \Zend_Db_Select
     */
    protected function getUserSelect($login_name, $organization)
    {
        /**
         * Read the needed parameters from the different tables, lots of renames
         * for compatibility across implementations.
         */
        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__user_logins', array(
                    'user_login_id'       => 'gul_id_user',
                    'user_two_factor_key' => 'gul_two_factor_key',
                    'user_enable_2factor' => 'gul_enable_2factor'
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
                    'user_embedded'       => 'gsf_is_embedded',
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
     * @param \Gems\User\User $user The user to check
     * @return boolean
     */
    public function hasPassword(\Gems\User\User $user)
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
     * @param \Gems\User\User $user The user whose password to change
     * @param string $password
     * @return \Gems\User\UserDefinitionInterface (continuation pattern)
     */
    public function setPassword(\Gems\User\User $user, $password)
    {
        throw new \Gems\Exception\Coding(sprintf('The password cannot be set for %s users.', get_class($this)));
        return $this;
    }
}
