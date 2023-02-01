<?php

namespace Gems\AuthTfa\Method;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
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
        [$size, $level] = $this->_getQRParams($params);

        $renderer = new ImageRenderer(
            new RendererStyle($size, 0),
            new ImagickImageBackEnd(),
        );
        $bacon    = new \BaconQrCode\Writer($renderer);
        $data     = $bacon->writeString($url, 'utf-8', \BaconQrCode\Common\ErrorCorrectionLevel::M());
        return 'data:image/png;base64,' . base64_encode($data);
    }

    protected function _getQRParams($params)
    {
        $size = !empty($params['width']) && (int) $params['width'] > 0 ? (int) $params['width'] : 200;
        $level = !empty($params['level']) && array_search($params['level'], array('L', 'M', 'Q', 'H')) !== false ? $params['level'] : 'M';

        return [$size, $level];
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
        $params['height'] = 200;
        $params['width']  = 200;
        $params['src']    = \MUtil\Html::raw($this->_getQRCodeInline(
            $name,
            $secret,
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
                'label' => $this->translator->trans('TFA key'),
                'disabled' => 'disabled',
                'readonly' => 'readonly',
            ])
            ->setValue($secret);

        $form->addElement($keyElement);
    }
}
