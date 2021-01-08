<?php


namespace Gems\User\TwoFactor;


interface SendTwoFactorCodeInterface
{
    public function getSentMessage(\Gems_User_User $user);

    /**
     * Reset the sent throttle so the otp can be retried in the valid time
     *
     * @param \Gems_User_User $user
     * @return bool
     */
    public function enableRetrySendCode(\Gems_User_User $user);

    /**
     * Send the OTP code to the user
     *
     * @param \Gems_User_User $user
     * @return bool
     */
    public function sendCode(\Gems_User_User $user);
}
