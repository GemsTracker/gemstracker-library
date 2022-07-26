<?php


namespace Gems\User\TwoFactor;


interface SendTwoFactorCodeInterface
{
    public function getSentMessage(\Gems\User\User $user);

    /**
     * Reset the sent throttle so the otp can be retried in the valid time
     *
     * @param \Gems\User\User $user
     * @return bool
     */
    public function enableRetrySendCode(\Gems\User\User $user);

    /**
     * Send the OTP code to the user
     *
     * @param \Gems\User\User $user
     * @return bool
     */
    public function sendCode(\Gems\User\User $user);
}
