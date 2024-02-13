<?php


namespace Gems\Legacy;


use Gems\User\User;
use Gems\User\UserLoader;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

class CurrentUserRepository
{
    protected ?User $currentUser = null;

    protected ?int $currentUserId = null;

    protected ProjectOverloader $loader;

    protected ?string $loginName = null;

    protected ?int $organizationId = null;

    protected ?string $role = null;

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

    public function getCurrentUserRole(): string|null
    {
        if ($this->role) {
            return $this->role;
        }
        if ($this->currentUser !== null) {
            $this->role = $this->currentUser->getRole();
            return $this->role;
        }

        return null;
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
        if (!$this->userLoader instanceof UserLoader) {
            $container =$this->loader->getContainer();
            $this->userLoader = $container->get(UserLoader::class);
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

    public function setCurrentUserRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * Return a list of allowed organizations for the user, if there is one.
     *
     * @return array<int, string>
     */
    public function getAllowedOrganizations(): array
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return [];
        }
        return $currentUser->getAllowedOrganizations();
    }

    /**
     * Throw an exception if the organization ID is not an allowed organization,
     * or if there is no logged in user. If the organizationId is null, we allow
     * access under the assumption that the code will use the currentOrganizationId.
     *
     * @param int|string|null $organizationId
     * @return void If the user has access to the organization
     * @throws \Gems\Exception If no user is logged in
     */
    public function assertAccessToOrganizationId(string|int|null $organizationId): void
    {
        if (is_null($organizationId)) {
            return;
        }
        $currentUser = $this->getCurrentUser();
        if ($currentUser) {
            $currentUser->assertAccessToOrganizationId($organizationId);
            return;
        }

        throw new \Gems\Exception('Inaccessible or unknown organization', 403);
    }
}
