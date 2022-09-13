<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;
use Gems\Cache\HelperAdapter;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailOtp extends AbstractOtpSendDecorator implements SendsOtpCodeInterface
{
    use ThrottleTrait;

    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        OtpAdapterInterface $otp,
        private readonly HelperAdapter $throttleCache,
        private readonly User $user,
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

    public function sendCode(): bool
    {
        if ($this->canSendOtp($this->user)) {
            $subject = 'Authentication code';

            $code = $this->otp->generateCode();

            $body = 'Your code is ' . $code;

            $result = $this->user->sendMail($subject, $body);
            if ($result === null) {
                $this->hitSendOtp($this->user);
                return true;
            }
        }

        throw new \Gems\Exception($this->translator->trans('OTP could not be sent, maximum number of OTP send attempts reached'));
    }

    public function getSentFeedbackMessage(): string
    {
        return $this->translator->trans('An authentication code has been sent to your email address');
    }
}
