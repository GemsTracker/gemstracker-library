<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;
use Gems\Cache\HelperAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailOtp extends AbstractOtpSendDecorator implements SendsOtpCodeInterface
{
    use ThrottleTrait;

    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        OtpAdapterInterface $otp,
        private readonly HelperAdapter $throttleCache,
    ) {
        parent::__construct($translator, $otp);

        $this->initThrottleTrait(
            isset($settings['maxSendOtpAttempts']) ? (int)$settings['maxSendOtpAttempts'] : null,
            isset($settings['maxSendOtpAttemptsPerPeriod']) ? (int)$settings['maxSendOtpAttemptsPerPeriod'] : null,
        );
    }

    private function getThrottleCache(): HelperAdapter
    {
        return $this->throttleCache;
    }

    public function sendCode(\Gems\User\User $user): bool
    {
        if ($this->canSendOtp($user)) {
            $subject = 'Authentication code';

            $code = $this->otp->generateCode();

            $body = 'Your code is ' . $code;

            $result = $user->sendMail($subject, $body);
            if ($result === null) {
                $this->hitSendOtp($user);
                return true;
            }
        }

        throw new \Gems\Exception($this->translator->trans('OTP could not be sent, maximum number of OTP send attempts reached'));
    }

    public function getSentFeedbackMessage(\Gems\User\User $user): string
    {
        return $this->translator->trans('An authentication code has been sent to your email address');
    }
}
