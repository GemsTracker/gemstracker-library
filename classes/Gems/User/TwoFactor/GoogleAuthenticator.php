<?php

/**
 * PHP Class for handling Google Authenticator 2-factor authentication.
 *
 * @author Michael Kliewe
 * @copyright 2012 Michael Kliewe
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 *
 * @link http://www.phpgangsta.de/
 */

namespace Gems\User\TwoFactor;

class GoogleAuthenticator extends TwoFactorTotpAbstract
{
    /**
     *
     * @var int
     */
    protected $_codeLength = 6;

    protected $_codeValidSeconds = 30;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * Get QR-Code URL for image, from google charts.
     *
     * @param string $name The person using it
     * @param string $secret Above code in authenticator
     * @param string $title
     * @param array  $params
     *
     * @return string
     */
    protected function _getQRCodeGoogleUrl($name, $secret, $title = null, $params = array())
    {
        $url = $this->_getQRCodeUrl($title, $name, $secret);
        list($width, $height, $level) = $this->_getQRParams($params);

        return 'https://chart.googleapis.com/chart?chs=' . $width . 'x' . $height . '&chld=' . $level . '|0&cht=qr&chl=' . $url . '';
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
    protected function _getQRCodeInline($name, $secret, $title = null, $params = array())
    {
        $url = $this->_getQRCodeUrl($title, $name, $secret);
        list($width, $height, $level) = $this->_getQRParams($params);

        $renderer = new \BaconQrCode\Renderer\Image\Png();
        $renderer->setWidth($width)
                 ->setHeight($height)
                 ->setMargin(0);
        $bacon    = new \BaconQrCode\Writer($renderer);
        $data     = $bacon->writeString($url, 'utf-8', \BaconQrCode\Common\ErrorCorrectionLevel::M);
        return 'data:image/png;base64,' . base64_encode($data);
    }

    protected function _getQRParams($params)
    {
        $width  = !empty($params['width']) && (int) $params['width'] > 0 ? (int) $params['width'] : 200;
        $height = !empty($params['height']) && (int) $params['height'] > 0 ? (int) $params['height'] : 200;
        $level  = !empty($params['level']) && array_search($params['level'], array('L', 'M', 'Q', 'H')) !== false ? $params['level'] : 'M';

        return [$width, $height, $level];
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
    protected function _getQRCodeUrl($title, $name, $secret)
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
     * @param \Gems\User\User $user The user to setup for
     * @param array $formData Current form data
     */
    public function addSetupFormElements(\Zend_Form $form, \Gems\User\User $user, array &$formData)
    {
        $name  = $user->getLoginName();
        $title = $this->project->getName() . ' - GemsTracker';

        $params['alt']    = $this->_('QR Code');
        $params['class']  = 'floatLeft';
        $params['height'] = 200;
        $params['width']  = 200;
        $params['src']    = \MUtil\Html::raw($this->_getQRCodeInline(
                $name,
                $formData['twoFactorKey'],
                $title,
                $params
                ));
        // \MUtil\EchoOut\EchoOut::track($params);

        $imgElement = $form->createElement('Html', 'image');
        $imgElement->setLabel($this->_('Scan this QR Code'))
                ->setDescription($this->_('Install the Google Authenticator app on your phone and scan this code.'));
        $imgElement->img($params);
        $form->addElement($imgElement);

        parent::addSetupFormElements($form, $user, $formData);
    }

    /**
     * The description that should be shown with the Enter code form element
     *
     * @return string
     */
    public function getCodeInputDescription()
    {
        return $this->_('From the Google app on your phone.');
    }
}
