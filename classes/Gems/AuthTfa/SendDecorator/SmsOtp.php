<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;
use Gems\Cache\HelperAdapter;
use Gems\Communication\Http\SmsClientInterface;
use Gems\User\Filter\DutchPhonenumberFilter;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class SmsOtp extends AbstractOtpSendDecorator implements SendsOtpCodeInterface
{
    use ThrottleSendTrait;

    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        OtpAdapterInterface $otp,
        private readonly SmsClientInterface $smsClient,
        private readonly HelperAdapter $throttleCache,
        private readonly User $user,
    ) {
        parent::__construct($translator, $otp);

        $this->initThrottleSendTrait(
            isset($settings['maxSendOtpAttempts']) ? (int)$settings['maxSendOtpAttempts'] : null,
            isset($settings['maxSendOtpAttemptsPerPeriod']) ? (int)$settings['maxSendOtpAttemptsPerPeriod'] : null,
        );
    }

    private function getThrottleCache(): HelperAdapter
    {
        return $this->throttleCache;
    }

    public function sendCode(): bool
    {
        if ($this->canSendOtp($this->user)) {
            $code = $this->otp->generateCode();

            $body = sprintf($this->translator->trans('Please authenticate with this number: %s'), $code);

            $phonenumber = $this->user->getPhonenumber();
            $filter = new DutchPhonenumberFilter();

            $result = $this->smsClient->sendMessage($filter->filter($phonenumber), $body);
            if ($result === true) {
                $this->hitSendOtp($this->user);
                return true;
            }
        }
        throw new \Gems\Exception($this->translator->trans('OTP could not be sent, maximum number of OTP send attempts reached'));
    }

    public function getSentFeedbackMessage(): string
    {
        return $this->translator->trans('An authentication code has been sent to your phone by sms');
    }
}
