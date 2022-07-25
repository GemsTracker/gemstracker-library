<?php

namespace Gems\User\TwoFactor;


use Gems\Communication\Http\SmsClientInterface;
use Gems\User\Filter\DutchPhonenumberFilter;

class SmsHotp extends TwoFactorHotpAbstract implements SendTwoFactorCodeInterface
{
    use CacheTrottle;
    use RetrySendCode;

    protected $_codeValidSeconds = 300;

    /**
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * @var SmsClientInterface
     */
    protected $smsClient;

    public function addSetupFormElements(\Zend_Form $form, \Gems\User\User $user, array &$formData)
    {
        $filter = new DutchPhonenumberFilter();
        $currentValue = '+' . $filter->filter($user->getPhonenumber());

        $element = $form->createElement('exhibitor', 'mobile',
            [
                'label' => $this->_('Mobile phone'),
                'value' => $currentValue,
            ]);
        $form->addElement($element);

        parent::addSetupFormElements($form, $user, $formData);

        if ($user->getPhonenumber() === null) {
            throw new \Gems\Exception($this->_('No mobile phonenumber set'));
        }
    }

    public function afterRegistry()
    {
        parent::afterRegistry();
        $this->initCacheThrottle();
        $this->smsClient = $this->loader->getCommunicationLoader()->getSmsClient();
    }

    public function getSentMessage(\Gems\User\User $user)
    {
        return $this->_('An authentication code has been sent to your phone by sms');
    }

    /**
     * The description that should be shown with the Enter code form element
     *
     * @return string
     */
    public function getCodeInputDescription()
    {
        return $this->_('From the sms we sent to your phonenumber.');
    }

    public function sendCode(\Gems\User\User $user)
    {
        if ($this->canSendOtp($user)) {
            $secret = $user->getTwoFactorKey();
            $code = $this->getNewCode($secret, $user);

            $body = sprintf($this->_('Please authenticate with this number: %s'), $code);

            $phonenumber = $user->getPhonenumber();
            $filter = new DutchPhonenumberFilter($phonenumber);

            $result = $this->smsClient->sendMessage($filter->filter($phonenumber), $body);
            if ($result === true) {
                $this->hitSendOtp($user);
                return true;
            }
        }
        throw new \Gems\Exception($this->_('OTP could not be sent'));
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
