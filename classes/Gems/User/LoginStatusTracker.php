<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User;

/**
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 Jun 28, 2018 11:35:35 AM
 */
class LoginStatusTracker
{
    /**
     * Stored in session;
     *
     * @var \Zend_Session_Namespace
     */
    protected $_session;

    /**
     *
     * @var \Gems_User_User
     */
    protected $_user;

    /**
     *
     * @var \Gems_User_UserLoader
     */
    protected $_userLoader;

    /**
     * Initiate session namespace
     */
    public function __construct(\Gems_User_UserLoader $userLoader)
    {
        $this->_session = new \Zend_Session_Namespace(__CLASS__ . GEMS_PROJECT_NAME . '.sessionStatic');

        if (! $this->_session->data) {
            $this->_session->data = $this->_getDefaults();
        }

        $this->_userLoader = $userLoader;
    }

    /**
     *
     * @return array
     */
    protected function _getDefaults()
    {
        return [
            'passwordResetting'      => false,
            'passwordText'           => null,
            'userName'               => null,
            'userOrganization'       => null,
            ];
    }

    /**
     * Cleanup after login
     */
    public function destroySession()
    {
        $this->_session->unsetAll();
    }

    /**
     *
     * @return string
     */
    public function getPasswordText()
    {
        return $this->_session->data['passwordText'];
    }

    /**
     *
     * @return int
     */
    public function getUsedOrganizationId()
    {
        return $this->_session->data['userOrganization'];
    }

    /**
     *
     * @return \Gems_User_User
     */
    public function getUser()
    {
        if ((! $this->_user) && $this->hasUser()) {
            $this->_user = $this->_userLoader->getUser(
                    $this->_session->data['userName'],
                    $this->_session->data['userOrganization']
                    );
        }
        return $this->_user;
    }

    /**
     *
     * @return boolean
     */
    public function hasUser()
    {
        // Add $this->_user here as the session reset may loose the session data
        return $this->_user || ($this->_session->data['userName'] && $this->_session->data['userOrganization']);
    }

    /**
     *
     * @return boolean
     */
    public function isPasswordResetActive()
    {
        return $this->_session->data['passwordResetting'];
    }

    /**
     *
     * @param boolean $value
     * @return $this
     */
    public function setPasswordResetActive($value = true)
    {
        $this->_session->data['passwordResetting'] = $value;

        return $this;
    }

    /**
     *
     * @param string $password
     * @return $this
     */
    public function setPasswordText($password)
    {
        $this->_session->data['passwordText'] = $password;
        return $this;
    }

    /**
     *
     * @param \Gems_User_User $user
     * @return $this
     */
    public function setUser(\Gems_User_User $user)
    {
        $this->_session->data['userName']         = $user->getLoginName();
        $this->_session->data['userOrganization'] = $user->getCurrentOrganizationId();

        $this->_user = $user;

        return $this;
    }
}
