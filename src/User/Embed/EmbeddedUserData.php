<?php

namespace Gems\User\Embed;

use Gems\User\User;

/**
 * @template T
 * @extends \ArrayObject<string, T>
 */
class EmbeddedUserData extends \ArrayObject
{
    /**
     * Required
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var EmbedLoader
     */
    protected $embedLoader;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;


    public function __construct($userId, \Zend_Db_Adapter_Abstract $db, \Gems\Loader $loader)
    {
        $this->offsetSet('user_id', $userId);

        $this->db     = $db;
        $this->loader = $loader;

        $this->embedLoader = $this->loader->getEmbedLoader();

        $this->refreshEmbeddingData();
    }

    /**
     * If the user deferred to does not exist, should it be created?
     *
     * @return boolean
     */
    public function canCreateUser()
    {
        return (bool) $this->offsetGet('gsus_create_user');
    }

    /**
     *
     * @return \Gems\User\Embed\EmbeddedAuthInterface|null
     */
    public function getAuthenticator()
    {
        $authenticationClassName = $this->offsetGet('gsus_authentication');
        if ($authenticationClassName) {
            return $this->embedLoader->loadAuthenticator($authenticationClassName);
        }
    }

    /**
     *
     * @return string One of the EmbedLoader->listCrumbOptions() options
     */
    public function getCrumbOption()
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
    public function getDeferredUser(User $embeddedUser, $deferredLogin)
    {
        $userLoader = $this->getUserLoader();

        if ($userLoader instanceof DeferredUserLoaderInterface) {
            return $userLoader->getDeferredUser($embeddedUser, $deferredLogin);
        }
    }

    /**
     *
     * @return string
     */
    public function getMvcLayout()
    {
        return $this->offsetGet('gsus_deferred_mvc_layout');
    }

    /**
     *
     * @return \Gems\User\Embed\RedirectInterface|null
     */
    public function getRedirector()
    {
        $redirectorClassName = $this->offsetGet('gsus_redirect');
        if ($redirectorClassName) {
            return $this->embedLoader->loadRedirect($redirectorClassName);
        }
    }

    /**
     *
     * @return string
     */
    public function getUserStyle()
    {
        return $this->offsetGet('gsus_deferred_user_layout');
    }

    /**
     *
     * @return int Group id or null
     */
    public function getUserGroupId()
    {
        return $this->offsetGet('gsus_deferred_user_group');
    }

    /**
     * Returns the user id, that identifies this user within this installation.
     *
     * @return int
     */
    public function getUserId()
    {
        return (int) $this->offsetGet('user_id');
    }

    /**
     * Returns the user id, that identifies this user within this installation.
     *
     * @return \Gems\User\Embed\DeferredUserLoaderInterface
     */
    public function getUserLoader()
    {
        $className = $this->offsetGet('gsus_deferred_user_loader');
        if ($className) {
            return $this->embedLoader->loadDeferredUserLoader($className);
        }
        throw new \Exception('No deferred user loader found');
    }

    /**
     * Load and set the embedded user data. triggered only when
     * embedded data is requested
     *
     * @return void
     */
    protected function refreshEmbeddingData()
    {
        $data = $this->db->fetchRow(
                "SELECT * FROM gems__systemuser_setup WHERE gsus_id_user = ?",
                $this->getUserId()
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
                'gsus_deferred_user_layout' => null,
                ];
        }

        foreach ($data as $key => $value) {
            // Using the full field name to prevent any future clash with a new or user specific field
            $this->offsetSet($key, $value);
        }
    }
}
