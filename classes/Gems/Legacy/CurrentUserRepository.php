<?php


namespace Gems\Legacy;


use Gems\User\User;
use Gems\User\UserLoader;
use Zalt\Loader\ProjectOverloader;

class CurrentUserRepository
{
    protected ?User $currentUser;

    protected ProjectOverloader $loader;

    protected ?string $loginName;

    protected ?int $organizationId;

    protected ?UserLoader $userLoader;

    public function __construct(ProjectOverloader $loader)
    {
        $this->loader = $loader;
    }

    public function getCurrentUser(): ?User
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

    protected function getUserLoader(): UserLoader
    {
        if (!$this->userLoader instanceof \Gems\User\UserLoader) {
            $this->userLoader = $this->loader->create('User\\UserLoader', $this->loader, ['User']);
        }

        return $this->userLoader;
    }

    public function setCurrentUser(User $user): void
    {
        $this->currentUser = $user;
    }

    public function setCurrentUserCredentials(string $loginName, int $organizationId): void
    {
        $this->loginName = $loginName;
        $this->organizationId = $organizationId;
    }
}
