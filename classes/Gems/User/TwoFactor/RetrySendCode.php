<?php


namespace Gems\User\TwoFactor;


trait RetrySendCode
{
    public function enableRetrySendCode(\Gems_User_User $user)
    {
        if ($this->canRetry($user)) {
            \MUtil_Echo::track('CLEAR');
            $this->clearShortOtpThrottle($user);
        }
    }
}
