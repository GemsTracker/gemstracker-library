<?php

namespace Gems\User\TwoFactor;


class MailHotp extends TwoFactorHotpAbstract implements SendTwoFactorCodeInterface
{
    use CacheTrottle;
    use RetrySendCode;

    protected $_codeValidSeconds = 300;

    public function afterRegistry()
    {
        $this->initCacheThrottle();
        $this->maxRetries = 2;
    }

    public function addSetupFormElements(\Zend_Form $form, \Gems_User_User $user, array &$formData)
    {
        $element = $form->createElement('exhibitor', 'email',
            [
                'label' => $this->_('E-mail'),
                'value' => $user->getEmailAddress(),
            ]);
        $form->addElement($element);

        parent::addSetupFormElements($form, $user, $formData);
    }

    /**
     * The description that should be shown with the Enter code form element
     *
     * @return string
     */
    public function getCodeInputDescription()
    {
        return $this->_('From the E-mail we sent you');
    }

    public function getSentMessage(\Gems_User_User $user)
    {
        return $this->_('An authentication code has been sent to your E-mail address');
    }

    public function sendCode(\Gems_User_User $user)
    {
        if ($this->canSendOtp($user)) {
            $subject = 'Authentication code';

            $secret = $user->getTwoFactorKey();
            $code = $this->getNewCode($secret, $user);

            $body = 'Your code is ' . $code;

            $result = $user->sendMail($subject, $body);
            if ($result === null) {
                $this->hitSendOtp($user);
                return true;
            }
        }
        throw new \Gems_Exception($this->_('OTP could not be sent'));
    }

    /**
     * Update the Two Factor Settings
     *
     * @param array $settings
     */
    public function updateSettings(array $settings)
    {
        parent::updateSettings($settings);

        if (isset($settings['maxSendTimesOfSameOtp'])) {
            $this->maxSendTimesOfSameOtp = (int)$settings['maxSendTimesOfSameOtp'];
        }
        if (isset($settings['maxSendOtpAttempts'])) {
            $this->maxSendOtpAttempts = (int)$settings['maxSendOtpAttempts'];
        }
        if (isset($settings['maxSendOtpAttemptsPerPeriod'])) {
            $this->maxSendOtpAttemptsPerPeriod = (int)$settings['maxSendOtpAttemptsPerPeriod'];
        }
    }
}
