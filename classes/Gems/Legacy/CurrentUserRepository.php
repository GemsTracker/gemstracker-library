<?php


namespace Gems\Legacy;


use Zalt\Loader\ProjectOverloader;

class CurrentUserRepository
{
    protected $currentUser;

    protected $loader;

    protected $loginName;

    protected $organizationId;

    protected $session;

    protected $userLoader;

    public function __construct(ProjectOverloader $loader, \Zend_Session_Namespace $LegacySession)
    {
        $this->loader = $loader;

        $this->session = $LegacySession;
    }

    public function getCurrentUser()
    {
        if (!$this->currentUser instanceof \Gems_User_User) {
            /*if ($this->loginName === null || $this->organizationId === null) {
                throw new \Exception('No user credentials set');
            }*/

            $userLoader = $this->getUserLoader();
            $user = $userLoader->getUser($this->loginName, $this->organizationId);

            $this->currentUser = $user;
        }

        return $this->currentUser;
    }

    public function getCurrentUserFromSession()
    {
        if ($this->session->user_role != 'nologin' && $this->session->__isset('__user_definition')) {
            $defName = 'User_' . $this->session->__get('__user_definition') . 'Definition';

            $userLoader = $this->getUserLoader();
            $this->currentUser = $this->loader->create('User_User', $this->session, $this->loader->create($defName));
            if ($this->currentUser) {
                $this->currentUser->answerRegistryRequest('userLoader', $userLoader);
                return $this->currentUser;
            }
            return null;
        }

        return null;
    }

    protected function getUserLoader()
    {
        if (!$this->userLoader instanceof \Gems_User_UserLoader) {
            $this->userLoader = $this->loader->create('User_UserLoader', $this->loader, ['User']);
        }

        return $this->userLoader;
    }

    public function setCurrentUserCredentials($loginName, $organizationId)
    {
        $this->loginName = $loginName;
        $this->organizationId = $organizationId;
    }
}
