<?php

namespace Gems\AuthTfa\Decorator;

use Gems\AuthTfa\Adapter\OtpInterface;
use Gems\Communication\Http\SmsClientInterface;
use Gems\User\Filter\DutchPhonenumberFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

class SmsOtp extends AbstractOtp implements SendsTfaCodeInterface
{
    public function __construct(
        TranslatorInterface $translator,
        OtpInterface $otp,
        private readonly SmsClientInterface $smsClient,
    ) {
        parent::__construct($translator, $otp);
    }

    public function sendCode(\Gems\User\User $user): bool
    {
        if ($this->canSendOtp($user)) {
            $code = $this->otp->generateCode();

            $body = sprintf($this->translator->trans('Please authenticate with this number: %s'), $code);

            $phonenumber = $user->getPhonenumber();
            $filter = new DutchPhonenumberFilter();

            $result = $this->smsClient->sendMessage($filter->filter($phonenumber), $body);
            if ($result === true) {
                $this->hitSendOtp($user);
                return true;
            }
        }
        throw new \Gems\Exception($this->translator->trans('OTP could not be sent'));
    }

    public function getSentFeedbackMessage(\Gems\User\User $user): string
    {
        return $this->translator->trans('An authentication code has been sent to your phone by sms');
    }
}
