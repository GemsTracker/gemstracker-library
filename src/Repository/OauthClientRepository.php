<?php

namespace Gems\Repository;

use Gems\Cache\HelperAdapter;

class OauthClientRepository
{
    public const CACHE_NAMESPACE = 'oauth_client_access_tokens';

    protected string|null $identifier = null;

    protected int $refreshBeforeEnd = 900;

    public function __construct(
        protected readonly HelperAdapter $cache
    )
    {
    }

    /**
     * Check if Access Token has been cached
     *
     * @return bool
     * @throws \Exception
     */
    public function hasAccessToken(string $identifier)
    {
        if ($this->cache->hasItem($identifier)) {
            return true;
        }

        return false;
    }

    /**
     * Get the current saved AccessToken
     *
     * @return string|null
     * @throws \Exception
     */
    public function getAccessToken(string $identifier)
    {
        $tokenInfo = $this->cache->getCacheItem($identifier);
        if ($tokenInfo !== false) {
            return $tokenInfo['access_token'];
        }
        return null;
    }

    /**
     * Get the current saved RefreshToken
     *
     * @return string|null
     * @throws \Exception
     */
    public function getRefreshToken(string $identifier)
    {
        $tokenInfo = $this->cache->getCacheItem($identifier);
        if ($tokenInfo !== false) {
            return $tokenInfo['refresh_token'];
        }
        return null;
    }

    /**
     * Is the saved Access Token still valid
     *
     * @return bool
     * @throws \Exception
     */
    public function accessTokenIsValid(string $identifier)
    {
        $tokenInfo = $this->cache->getCacheItem($identifier);
        if ($tokenInfo !== false) {
            $expiresAt = $tokenInfo['expires_at'];
            if (($expiresAt  - $this->refreshBeforeEnd) > time()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is the saved Refresh Token still valid
     *
     * @return bool
     * @throws \Exception
     */
    public function refreshTokenIsValid(string $identifier)
    {
        $tokenInfo = $this->cache->getCacheItem($identifier);
        if ($tokenInfo !== false) {
            $expiresAt = $tokenInfo['refresh_expires_at'];
            if ($expiresAt - 60 > time()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set a new Access Token in cache
     *
     * @param $accessToken
     * @param $expiresIn
     * @param null $refreshToken
     * @param null $refreshInterval
     * @throws \Zend_Cache_Exception
     */
    public function setAccessToken(string $identifier, string $accessToken, int $expiresIn, string|null $refreshToken = null, string|null $refreshInterval = null): void
    {
        $refreshExpiresAt = new \DateTime();
        if ($refreshInterval) {
            $refreshExpiresAt->add(new \DateInterval($refreshInterval));
        }

        $tokenInfo = [
            'access_token' => $accessToken,
            'expires_at' => time() + $expiresIn,
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $refreshExpiresAt->getTimestamp(),
        ];
        $this->cache->setCacheItem($identifier, $tokenInfo);
    }
}