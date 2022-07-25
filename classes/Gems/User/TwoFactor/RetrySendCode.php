<?php


namespace Gems\User\TwoFactor;


trait RetrySendCode
{
    public function enableRetrySendCode(\Gems\User\User $user)
    {
        if ($this->canRetry($user)) {
            \MUtil\EchoOut\EchoOut::track('CLEAR');
            $this->clearShortOtpThrottle($user);
        }
    }
}
