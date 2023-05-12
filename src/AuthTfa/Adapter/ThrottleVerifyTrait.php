<?php

namespace Gems\AuthTfa\Adapter;

use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Gems\User\User;

trait ThrottleVerifyTrait
{
    private RateLimiter $verifyRateLimiter;

    /**
     * @var int Maximum otp verify attempts per time period
     */
    private int $maxVerifyAttempts = 2;

    abstract private function getThrottleCache(): HelperAdapter;

    abstract function getCodeValidSeconds(): int;

    protected function initThrottleVerifyTrait(?int $maxVerifyAttempts = null): void
    {
        $this->verifyRateLimiter = new RateLimiter($this->getThrottleCache());

        if ($maxVerifyAttempts !== null) {
            $this->maxVerifyAttempts = $maxVerifyAttempts;
        }
    }

    private function getMaxVerifyOtpKey(User $user): string
    {
        return sha1($user->getUserId()) . '_otp_verify_max';
    }

    public function canVerifyOtp(User $user): bool
    {
        return !$this->verifyRateLimiter->tooManyAttempts($this->getMaxVerifyOtpKey($user), $this->maxVerifyAttempts);
    }

    public function hitVerifyOtp(User $user): void
    {
        $this->verifyRateLimiter->hit($this->getMaxVerifyOtpKey($user), $this->getOtpTimeLeft());
    }

    /**
     * @return int number of seconds the current code has left
     */
    protected function getOtpTimeLeft(): int
    {
        $now = time();
        $currentTimeSlice = (int)floor($now / $this->getCodeValidSeconds());
        $endTime = ($currentTimeSlice+1) * $this->getCodeValidSeconds();
        $secondsLeft = $endTime - $now;

        return (int)$secondsLeft;
    }
}
