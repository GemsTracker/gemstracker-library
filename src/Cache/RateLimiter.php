<?php

namespace Gems\Cache;

use Symfony\Component\Cache\CacheItem;

class RateLimiter
{
    protected array $tags = [];

    /**
     * @var string Suffix for the cache key for the timer function
     */
    protected string $timerSuffix = '_timer';

    public function __construct(
        protected readonly HelperAdapter $cache
    )
    {
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param string $key
     * @return int
     */
    public function attempts(string $key): int
    {
        if ($this->cache->hasItem($key)) {
            return $this->cache->getCacheItem($key) ?? 0;
        }
        return 0;
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param string $key
     * @return int
     */
    public function availableIn(string $key): int
    {
        $availableAt = $this->cache->getCacheItem($key . $this->timerSuffix);
        if ($availableAt instanceof \DateTimeInterface) {
            return $availableAt->getTimestamp() - time();
        }

        return 0;
    }

    /**
     * Clear the hits and lockout timer for the given key.
     *
     * @param string $key
     */
    public function clear(string $key): void
    {
        $this->resetAttempts($key);

        $this->cache->deleteItem($key . $this->timerSuffix);
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $now = new \DateTimeImmutable();
        $addTimeInterval = new \DateInterval('PT'. $decaySeconds . 'S');

        $timerKey = $key . $this->timerSuffix;
        $this->cache->setCacheItem($timerKey, $now->add($addTimeInterval), $this->tags, $decaySeconds);

        $attempts = $this->cache->getCacheItem($key);
        if ($attempts === null) {
            $attempts = 0;
        }

        $attempts += 1;

        $this->cache->setCacheItem($key, $attempts, $this->tags, $decaySeconds);

        return 1;
    }

    /**
     * Reset the number of attempts for the given key.
     *
     * @param string $key
     * @return bool
     */
    public function resetAttempts(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);

        return $maxAttempts - $attempts;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return bool
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->hasItem($key.$this->timerSuffix)) {
                return true;
            }

            $this->resetAttempts($key);
        }
        return false;
    }
}
