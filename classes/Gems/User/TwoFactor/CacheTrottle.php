<?php

namespace Gems\User\TwoFactor;


use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Psr\Cache\CacheItemPoolInterface;

trait CacheTrottle
{
    /**
     * @var HelperAdapter
     */
    public $cache;

    /**
     * @var RateLimiter
     */
    protected $rateLimiter;

    protected $maxRetries = 0;

    protected $maxSendTimesOfSameOtp = 1;

    protected $maxSendOtpAttempts = 3;

    /**
     * @var int Maxmumum otp send attempts per time period in seconds. Default per day (24 * 60 * 60)
     */
    protected $maxSendOtpAttemptsPeriod = 86400;

    protected function initCacheThrottle($maxSendTimesOfSameOtp = null, $maxSendOtpAttempts = null, $maxSendOtpAttemptsPerPeriod = null)
    {
        $cache = $this->cache;
        $this->rateLimiter = new RateLimiter($this->cache);

        if ($maxSendTimesOfSameOtp !== null) {
            $this->maxSendTimesOfSameOtp = $maxSendTimesOfSameOtp;
        }
        if ($maxSendOtpAttempts !== null) {
            $this->maxSendOtpAttempts = $maxSendOtpAttempts;
        }
        if ($maxSendOtpAttemptsPerPeriod !== null) {
            $this->maxSendOtpAttemptsPerPeriod = $maxSendOtpAttemptsPerPeriod;
        }
    }

    /**
     * Can new OTP's be sent?
     *
     * @param \Gems_User_User $user
     * @return bool
     */
    protected function canSendOtp(\Gems_User_User $user)
    {
        $key = $this->getSendOtpKey($user);
        $maxKey = $this->getMaxSendOtpKey($user);
        if ($this->rateLimiter->tooManyAttempts($key, $this->maxSendTimesOfSameOtp)) {
            throw new \Gems_Exception_Security($this->_('OTP already sent.'));
        }
        if ($this->rateLimiter->tooManyAttempts($maxKey, $this->maxSendOtpAttempts)) {
            throw new \Gems_Exception_Security($this->_('Maximum number of OTP send attempts reached'));
        }
        return true;
    }

    public function canRetry(\Gems_User_User $user)
    {
        $key = $this->getRetryOtpKey($user);
        $maxKey = $this->getMaxSendOtpKey($user);
        if ($this->rateLimiter->tooManyAttempts($key, $this->maxRetries)) {
            throw new \Gems_Exception_Security($this->_('Maximum number of OTP send attempts reached'));
        }
        if ($this->rateLimiter->tooManyAttempts($maxKey, $this->maxSendOtpAttempts)) {
            throw new \Gems_Exception_Security($this->_('Maximum number of OTP send attempts reached'));
        }
        return true;
    }

    protected function clearShortOtpThrottle(\Gems_User_User $user)
    {
        $key = $this->getSendOtpKey($user);
        $this->rateLimiter->clear($key);
    }

    /**
     * @return int number of seconds the current code has left
     */
    protected function getOtpTimeLeft()
    {
        $now = time();
        $currentTimeSlice = (int)floor($now / $this->_codeValidSeconds);
        $endTime = ($currentTimeSlice+1) * $this->_codeValidSeconds;
        $secondsLeft = $endTime - $now;

        return (int)$secondsLeft;
    }

    /**
     * Get OTP send throttle key
     *
     * @param \Gems_User_User $user
     * @return string
     */
    protected function getSendOtpKey(\Gems_User_User $user)
    {
        $key = sha1($user->getUserId()) . '_otp';
        return $key;
    }

    /**
     * Get OTP send throttle key
     *
     * @param \Gems_User_User $key
     * @return string
     */
    protected function getMaxSendOtpKey(\Gems_User_User $user)
    {
        $key = $this->getSendOtpKey($user) . '_max';
        return $key;
    }

    /**
     * Get OTP send throttle key
     *
     * @param \Gems_User_User $key
     * @return string
     */
    protected function getRetryOtpKey(\Gems_User_User $user)
    {
        $key = $this->getSendOtpKey($user) . '_retry';
        return $key;
    }

    /**
     * @param \Gems_User_User $user
     */
    protected function hitSendOtp(\Gems_User_User $user)
    {
        $key = $this->getSendOtpKey($user);
        $maxKey = $this->getMaxSendOtpKey($user);

        // Only limit the number of same sents on TOTP
        if ($this instanceof TwoFactorTotpAbstract) {
            $this->rateLimiter->hit($key, $this->getOtpTimeLeft());
        }
        $this->rateLimiter->hit($maxKey, $this->maxSendOtpAttemptsPeriod);
    }

    protected function hitOtpRetry(\Gems_User_User $user)
    {
        $key = $this->getRetryOtpKey($user);
        $this->rateLimiter->hit($key, $this->getOtpTimeLeft());
    }
}
