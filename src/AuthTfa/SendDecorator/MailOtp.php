<?php

namespace Gems\AuthTfa\SendDecorator;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;
use Gems\Cache\HelperAdapter;
use Gems\Communication\Exception;
use Gems\User\User;
use Gems\User\UserMailer;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailOtp extends AbstractOtpSendDecorator implements SendsOtpCodeInterface
{
    use ThrottleSendTrait;

    public function __construct(
        array $settings,
        TranslatorInterface $translator,
        OtpAdapterInterface $otp,
        private readonly HelperAdapter $throttleCache,
        private readonly User $user,
        private readonly UserMailer $userMailer,
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
            $subject = 'Authentication code';

            $code = $this->otp->generateCode($this->user);

            $body = 'Your code is ' . $code;

            $this->userMailer->sendMail($this->user, $subject, $body);

            $this->hitSendOtp($this->user);
            return true;
        }

        throw new \Gems\Exception($this->translator->trans('OTP could not be sent, maximum number of OTP send attempts reached'));
    }

    public function getSentFeedbackMessage(): string
    {
        return $this->translator->trans('An authentication code has been sent to your email address');
    }
}
