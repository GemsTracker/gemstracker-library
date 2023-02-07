<?php

namespace Gems\AuthTfa\Method;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Gems\AuthTfa\Adapter\TotpAdapter;
use Gems\Cache\HelperAdapter;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppTotp extends TotpAdapter implements OtpMethodInterface
{
    public function __construct(
        array $settings,
        private readonly TranslatorInterface $translator,
        private readonly User $user,
        HelperAdapter $throttleCache,
        private readonly array $config,
    ) {
        parent::__construct($settings, $throttleCache);
    }

    public function getCodeInputDescription(): string
    {
        return $this->translator->trans('From the TFA app on your phone.');
    }

    /**
     * Get QR-Code URL for image, using inline data uri.
     *
     * @param string $name The person using it
     * @param string $secret Above code in authenticator
     * @param string $title
     * @param array  $params
     *
     * @return string
     */
    protected function _getQRCodeInline(string $name, string $secret, ?string $title = null, array $params = [])
    {
        $url = $this->_getQRCodeUrl($title, $name, $secret);

        return (new QRCode(new QROptions([
            'eccLevel' => QRCode::ECC_M,
            'bgColor' => [255, 255, 255],
            'imageTransparent' => false,
            'scale' => 4,
        ])))->render($url);
    }

    /**
     * Get otpauth url
     *
     * @param $title
     * @param $name
     * @param $secret
     *
     * @return string
     */
    protected function _getQRCodeUrl(string $title, string $name, string $secret): string
    {
        if (empty($title)) {
            return 'otpauth://totp/'.rawurlencode($name).'?secret='.$secret;
        } else {
            return 'otpauth://totp/'.rawurlencode($title).':'.rawurlencode($name).'?secret='.$secret.'&issuer='.rawurlencode($title);
        }
    }

    /**
     * Add the elements to the setup form
     *
     * @param \Zend_Form $form
     */
    public function addSetupFormElements(\Zend_Form $form, array $formData)
    {
        $name  = $this->user->getLoginName();
        $title = $this->config['app']['name'] . ' - GemsTracker';

        $params['alt']    = $this->translator->trans('QR Code');
        $params['class']  = 'floatLeft';
        $params['src']    = \MUtil\Html::raw($this->_getQRCodeInline(
            $name,
            $formData['twoFactorKey'],
            $title,
            $params
        ));

        $imgElement = $form->createElement('Html', 'image');
        $imgElement
            ->setLabel($this->translator->trans('Scan this QR Code'))
            ->setDescription($this->translator->trans('Install the Google Authenticator app on your phone and scan this code.'))
            ->img($params);
        $form->addElement($imgElement);

        $keyElement = $form
            ->createElement('Text', 'twoFactorKey', [
                'label' => $this->translator->trans('New TFA key'),
                'disabled' => 'disabled',
                'readonly' => 'readonly',
            ])
            ->setValue($formData['twoFactorKey'])
            ->setDescription($this->translator->trans('The key corresponding to your new TFA. You can safely ignore it.'));

        $form->addElement($keyElement);

        $newKeyElement = new \MUtil\Form\Element\FakeSubmit('new_key');
        $newKeyElement->setLabel($this->translator->trans('Generate new key'))
            ->setAttrib('class', 'button btn btn-primary');
        $form->addElement($newKeyElement);

        $codeElement = $form
            ->createElement('Text', 'twoFactorCode', [
                'label' => $this->translator->trans('TFA code'),
                'required' => true,
            ])
            ->setDescription($this->translator->trans('Enter the 6-digit TFA code to confirm the new TFA'));

        $form->addElement($codeElement);
    }
}
