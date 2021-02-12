<?php

namespace Gems\Cache;

class RateLimiter
{
    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    protected $tags = [];

    /**
     * @var string Suffix for the cache key for the timer function
     */
    protected $timerSuffix = '_timer';

    public function __construct(\Zend_Cache_Core $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param string $key
     * @return int
     */
    public function attempts($key)
    {
        $test = $this->cache->load($key);

        if ($this->cache->test($key) !== false) {
            return $this->cache->load($key);
        }
        return 0;
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param string $key
     * @return int
     */
    public function availableIn($key)
    {
        $availableAt = $this->cache->load($key . $this->timerSuffix);
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
    public function clear($key)
    {
        $this->resetAttempts($key);

        $this->cache->remove($key . $this->timerSuffix);
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int
     */
    public function hit($key, $decaySeconds = 60)
    {
        $now = new \DateTimeImmutable();
        $addTimeInterval = new \DateInterval('PT'. $decaySeconds . 'S');

        $timerKey = $key . $this->timerSuffix;
        $this->cache->save($now->add($addTimeInterval), $timerKey, $this->tags, $decaySeconds);

        $attempts = $this->cache->load($key);
        if ($attempts === false) {
            $attempts = 0;
        }

        $attempts += 1;

        $this->cache->save($attempts, $key, $this->tags, $decaySeconds);

        return 1;
    }

    /**
     * Reset the number of attempts for the given key.
     *
     * @param string $key
     * @return bool
     */
    public function resetAttempts($key)
    {
        return $this->cache->remove($key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public function retriesLeft($key, $maxAttempts)
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
    public function tooManyAttempts($key, $maxAttempts)
    {
        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->test($key.$this->timerSuffix) !== false) {
                return true;
            }

            $this->resetAttempts($key);
        }
        return false;
    }
}
