<?php

namespace Gems\AuthTfa\Method;

use Gems\AuthTfa\Adapter\HotpAdapter;
use Gems\AuthTfa\SendDecorator\SmsOtp;
use Gems\Cache\HelperAdapter;
use Gems\Communication\Http\SmsClientInterface;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class SmsHotp extends SmsOtp implements OtpMethodInterface
{
    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        User $user,
        SmsClientInterface $smsClient,
        HelperAdapter $throttleCache,
    ) {
        parent::__construct($settings, $translator, new HotpAdapter($settings, $user), $smsClient, $throttleCache);
    }

    public function getCodeInputDescription(): string
    {
        return $this->translator->trans('From the sms we sent to your phonenumber.');
    }
}
