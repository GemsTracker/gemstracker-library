<?php

namespace Gems\AuthTfa\Decorator;

class MailOtp extends AbstractOtp implements SendsTfaCodeInterface
{
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

        throw new \Gems\Exception($this->translator->trans('OTP could not be sent'));
    }

    public function getSentFeedbackMessage(\Gems\User\User $user): string
    {
        return $this->translator->trans('An authentication code has been sent to your email address');
    }
}
