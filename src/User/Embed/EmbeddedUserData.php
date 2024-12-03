<?php

namespace Gems\User\Embed;

use Gems\Db\ResultFetcher;
use Gems\Encryption\ValueEncryptor;
use Gems\User\User;
use Gems\Util\IpAddress;

/**
 * @template T
 * @extends \ArrayObject<string, T>
 */
class EmbeddedUserData extends \ArrayObject
{
    protected string|null $decryptedKey = null;
    public function __construct(
        protected readonly int $userId,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly EmbedLoader $embedLoader,
        protected readonly ValueEncryptor $valueEncryptor,
    )
    {
        $this->offsetSet('user_id', $userId);

        $this->refreshEmbeddingData();
        parent::__construct();
    }

    /**
     * If the user deferred to does not exist, should it be created?
     *
     * @return boolean
     */
    public function canCreateUser(): bool
    {
        return (bool) $this->offsetGet('gsus_create_user');
    }

    public function getAllowedIPRanges(): string|null
    {
        return $this->offsetGet('gsus_allowed_ip_ranges');
    }

    /**
     *
     * @return \Gems\User\Embed\EmbeddedAuthInterface|null
     */
    public function getAuthenticator(): EmbeddedAuthInterface|null
    {
        $authenticationClassName = $this->offsetGet('gsus_authentication');
        if ($authenticationClassName) {
            return $this->embedLoader->loadAuthenticator($authenticationClassName);
        }
        return null;
    }

    /**
     *
     * @return string One of the EmbedLoader->listCrumbOptions() options
     */
    public function getCrumbOption(): string
    {
        return $this->offsetGet('gsus_hide_breadcrumbs');
    }

    /**
     * Shortcut function to get the deferred user
     *
     * @param User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return User|null
     */
    public function getDeferredUser(User $embeddedUser, string $deferredLogin): User|null
    {
        $userLoader = $this->getUserLoader();

        if ($userLoader instanceof DeferredUserLoaderInterface) {
            return $userLoader->getDeferredUser($embeddedUser, $deferredLogin);
        }
        return null;
    }

    /**
     *
     * @return string
     */
    public function getMvcLayout(): string
    {
        return $this->offsetGet('gsus_deferred_mvc_layout');
    }

    /**
     *
     * @return \Gems\User\Embed\RedirectInterface|null
     */
    public function getRedirector(): RedirectInterface|null
    {
        $redirectorClassName = $this->offsetGet('gsus_redirect');
        if ($redirectorClassName) {
            return $this->embedLoader->loadRedirect($redirectorClassName);
        }
        return null;
    }

    public function getSecretKey(): string|null
    {
        if ($this->decryptedKey === null) {
            $key = $this->offsetGet('gsus_secret_key');
            if ($key) {
                $this->decryptedKey = $this->valueEncryptor->decrypt($key);
            }
        }
        return $this->decryptedKey;
    }

    /**
     *
     * @return string
     */
    public function getUserStyle(): string
    {
        return $this->offsetGet('gsus_deferred_user_layout');
    }

    /**
     *
     * @return int|null Group id or null
     */
    public function getUserGroupId(): int|null
    {
        return $this->offsetGet('gsus_deferred_user_group');
    }

    /**
     * Returns the user id, that identifies this user within this installation.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->offsetGet('user_id');
    }

    /**
     * Returns the user id, that identifies this user within this installation.
     *
     * @return \Gems\User\Embed\DeferredUserLoaderInterface
     */
    public function getUserLoader(): DeferredUserLoaderInterface
    {
        $className = $this->offsetGet('gsus_deferred_user_loader');
        if ($className) {
            return $this->embedLoader->loadDeferredUserLoader($className);
        }
        throw new \Exception('No deferred user loader found');
    }

    public function isAllowedIpForLogin(?string $ipAddress): bool
    {
        if (empty($ipAddress)) {
            return false;
        }

        // Check group list
        if (!IpAddress::isAllowed($ipAddress, $this->getAllowedIPRanges() ?? '')) {
            return false;
        }

        return true;
    }

    /**
     * Load and set the embedded user data. triggered only when
     * embedded data is requested
     *
     * @return void
     */
    protected function refreshEmbeddingData(): void
    {
        $data = $this->resultFetcher->fetchRow(
                "SELECT * FROM gems__systemuser_setup WHERE gsus_id_user = ?",
                [$this->getUserId()]
                );

        if ($data) {
            unset($data['gsus_id_user'], $data['gsus_changed'], $data['gsus_changed_by'],
                    $data['gsus_created'], $data['gsus_created_by']);
        } else {
            // Load defaults
            $data = [
                'gsus_secret_key'           => null,
                'gsus_create_user'          => 0,
                'gsus_authentication'       => null,
                'gsus_deferred_user_loader' => null,
                'gsus_deferred_user_group'  => null,
                'gsus_redirect'             => null,
                'gsus_allowed_ip_ranges'    => null,
                'gsus_deferred_user_layout' => null,
                ];
        }

        foreach ($data as $key => $value) {
            // Using the full field name to prevent any future clash with a new or user specific field
            $this->offsetSet($key, $value);
        }
    }
}
