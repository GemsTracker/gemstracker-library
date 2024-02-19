<?php

namespace Gems\AuthTfa\Method;

use Gems\AuthTfa\Adapter\HotpAdapter;
use Gems\AuthTfa\SendDecorator\MailOtp;
use Gems\Cache\HelperAdapter;
use Gems\User\User;
use Gems\User\UserMailer;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailHotp extends MailOtp implements OtpMethodInterface
{
    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        User $user,
        HelperAdapter $throttleCache,
        UserMailer $userMailer,
    ) {
        parent::__construct(
            $settings,
            $translator,
            new HotpAdapter($settings, $throttleCache),
            $throttleCache,
            $user,
            $userMailer,
        );
    }

    public function getCodeInputDescription(): string
    {
        return $this->translator->trans('From the E-mail we sent you');
    }

    public function addSetupFormElements(\Zend_Form $form, array $formData)
    {
    }
}
