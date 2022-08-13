<?php


namespace Gems\User\TwoFactor;


interface UserOtpInterface
{
    public function setUserId($userId);

    public function setUserOtpCount($count);

    public function setUserOtpRequested(\DateTimeInterface $requestedTime);
}
