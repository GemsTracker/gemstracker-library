<?php


namespace Gems\Legacy;


use Gems\User\User;
use Gems\User\UserLoader;
use Zalt\Loader\ProjectOverloader;

class CurrentUserRepository
{
    protected ?User $currentUser = null;

    protected ?int $currentUserId = null;

    protected ProjectOverloader $loader;

    protected ?string $loginName = null;

    protected ?int $organizationId = null;

    protected ?UserLoader $userLoader = null;

    public function __construct(ProjectOverloader $loader)
    {
        $this->loader = $loader;
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->currentUser instanceof \Gems\User\User) {
            if ($this->loginName === null || $this->organizationId === null) {
                return null;
            }

            $userLoader = $this->getUserLoader();
            $user = $userLoader->getUser($this->loginName, $this->organizationId);

            $userLoader->setLegacyCurrentUser($user);

            $this->currentUser = $user;
        }

        return $this->currentUser;
    }

    public function getCurrentLoginName(): string|null
    {
        if ($this->loginName) {
            return $this->loginName;
        }
        if ($this->currentUser !== null) {
            $this->loginName = $this->currentUser->getLoginName();
            return $this->loginName;
        }

        return null;
    }

    public function getCurrentOrganizationId(): int
    {
        if ($this->organizationId !== null) {
            return $this->organizationId;
        }
        if ($this->currentUser !== null) {
            $this->organizationId = $this->currentUser->getCurrentOrganizationId();
            return $this->organizationId;
        }

        return UserLoader::SYSTEM_NO_ORG;
    }

    public function getCurrentUserId(): int
    {
        if ($this->currentUserId !== null) {
            return $this->currentUserId;
        }
        if ($this->currentUser !== null) {
            $this->currentUserId = $this->currentUser->getUserId();
            return $this->currentUserId;
        }

        return UserLoader::UNKNOWN_USER_ID;
    }

    protected function getUserLoader(): UserLoader
    {
        if (!$this->userLoader instanceof \Gems\User\UserLoader) {
            $this->userLoader = $this->loader->create('User\\UserLoader', $this->loader, ['User']);
        }

        return $this->userLoader;
    }

    /**
     * Returns the organization that is currently used by this user.
     *
     * @return \Gems\User\Organization
     */
    public function getCurrentOrganization()
    {
        $userLoader = $this->getUserLoader();
        return $userLoader->getOrganization($this->getCurrentOrganizationId());
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

    /**
     * @param int $currentUserId
     */
    public function setCurrentUserId(int $currentUserId): void
    {
        $this->currentUserId = $currentUserId;
    }

    /**
     * @param int $organizationId
     */
    public function setCurrentOrganizationId(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }
}
