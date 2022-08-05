<?php


namespace Gems\Legacy;


use Zalt\Loader\ProjectOverloader;

class CurrentUserRepository
{
    protected $currentUser;

    protected $loader;

    protected $loginName;

    protected $organizationId;

    protected $userLoader;

    public function __construct(ProjectOverloader $loader)
    {
        $this->loader = $loader;
    }

    public function getCurrentUser()
    {
        if (!$this->currentUser instanceof \Gems\User\User) {
            /*if ($this->loginName === null || $this->organizationId === null) {
                throw new \Exception('No user credentials set');
            }*/

            $userLoader = $this->getUserLoader();
            $user = $userLoader->getUser($this->loginName, $this->organizationId);

            $userLoader->setLegacyCurrentUser($user);
            
            $this->currentUser = $user;
        }

        return $this->currentUser;
    }

    protected function getUserLoader()
    {
        if (!$this->userLoader instanceof \Gems\User\UserLoader) {
            $this->userLoader = $this->loader->create('User\\UserLoader', $this->loader, ['User']);
        }

        return $this->userLoader;
    }

    public function setCurrentUserCredentials($loginName, $organizationId)
    {
        $this->loginName = $loginName;
        $this->organizationId = $organizationId;
    }
}
