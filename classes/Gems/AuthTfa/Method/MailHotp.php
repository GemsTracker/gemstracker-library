<?php

namespace Gems\AuthTfa\Method;

use Gems\AuthTfa\Adapter\HotpAdapter;
use Gems\AuthTfa\SendDecorator\MailOtp;
use Gems\Cache\HelperAdapter;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailHotp extends MailOtp implements OtpMethodInterface
{
    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        User $user,
        HelperAdapter $throttleCache,
    ) {
        parent::__construct($settings, $translator, new HotpAdapter($settings, $user), $throttleCache, $user);
    }

    public function getCodeInputDescription(): string
    {
        return $this->translator->trans('From the E-mail we sent you');
    }
}
