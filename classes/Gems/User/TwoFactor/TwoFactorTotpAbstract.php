<?php

namespace Gems\User\TwoFactor;


abstract class TwoFactorTotpAbstract extends \MUtil_Translate_TranslateableAbstract implements TwoFactorAuthenticatorInterface
{
    /**
     *
     * @var int length of the TOTP code
     */
    protected $_codeLength = 6;

    /**
     * @var int number of seconds a code is valid
     */
    protected $_codeValidSeconds = 30;

    /**
     * @var int size of the TOTP secret
     */
    protected $_secretLength = 64;

    /**
     * @var int number of earlier codes that are still valid. 1 is only current
     */
    protected $_verifyDiscrepancy = 2;

    public function __construct(array $settings=null)
    {
        if ($settings) {
            $this->updateSettings($settings);
        }
    }

    public function addSetupFormElements(\Zend_Form $form, \Gems_User_User $user, array &$formData)
    {
        $this->addKeyEditFormElements($form, $user, $formData);
    }

    protected function addKeyEditFormElements(\Zend_Form $form, \Gems_User_User $user, array &$formData)
    {
        if ($user->canSaveTwoFactorKey()) {
            $orElement = $form->createElement('Html', 'orelem');
            $orElement->setLabel($this->_('or'));
            $form->addElement($orElement);

            $options = [
                'label' => $this->_('Enter new authenticator code'),
                'description' => $this->_('An uppercase string containing A through Z, 2 to 7 and maybe = at the end.'),
                'maxlength' => $this->_secretLength,
                'minlength' => $this->_secretLength,
                'required'  => true,
                'size'      => floor($this->_secretLength * 1.5),
            ];

            $keyElement = $form->createElement('Text', 'twoFactorKey', $options);
            $keyElement->addFilter('StringToUpper')
                ->addValidator('Base32')
                ->addValidator('StringLength', true, ['min' => $this->_secretLength, 'max' => $this->_secretLength]);

            $form->addElement($keyElement);
        }
    }

    /**
     * Helper class to decode base32.
     *
     * @param $secret
     *
     * @return bool|string
     */
    protected function _base32Decode($secret)
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = $this->_getBase32LookupTable();
        $base32charsFlipped = array_flip($base32chars);

        $paddingCharCount = substr_count($secret, $base32chars[32]);
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        for ($i = 0; $i < 4; ++$i) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32], $allowedValues[$i])) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32chars)) {
                return false;
            }
            for ($j = 0; $j < 8; ++$j) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); ++$z) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }

    /**
     * Get array with all 32 characters for decoding from/encoding to base32.
     *
     * @return array
     */
    protected function _getBase32LookupTable()
    {
        return array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
            'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
            '=',  // padding char
        );
    }

    /**
     * A timing safe equals comparison
     * more info here: http://blog.ircmaxell.com/2014/11/its-all-about-time.html.
     *
     * @param string $safeString The internal (safe) value to be checked
     * @param string $userString The user submitted (unsafe) value
     *
     * @return bool True if the two strings are identical
     */
    protected function _timingSafeEquals($safeString, $userString)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($safeString, $userString);
        }
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);

        if ($userLen != $safeLen) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $userLen; ++$i) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return $result === 0;
    }

    /**
     * Create new secret.
     * 16 characters, randomly chosen from the allowed base32 characters.
     *
     * @param int $secretLength
     *
     * @return string
     */
    public function createSecret($secretLength = null)
    {
        if ($secretLength === null) {
            $secretLength = $this->_secretLength;
        }

        $validChars = $this->_getBase32LookupTable();

        // Valid secret lengths are 80 to 640 bits
        if ($secretLength < 16 || $secretLength > 128) {
            throw new Exception('Bad secret length');
        }
        $secret = '';
        $rnd = false;
        if (function_exists('random_bytes')) {
            $rnd = random_bytes($secretLength);
        } elseif (function_exists('mcrypt_create_iv')) {
            $rnd = mcrypt_create_iv($secretLength, MCRYPT_DEV_URANDOM);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rnd = openssl_random_pseudo_bytes($secretLength, $cryptoStrong);
            if (!$cryptoStrong) {
                $rnd = false;
            }
        }
        if ($rnd !== false) {
            for ($i = 0; $i < $secretLength; ++$i) {
                $secret .= $validChars[ord($rnd[$i]) & 31];
            }
        } else {
            throw new Exception('No source of secure random');
        }

        return $secret;
    }

    /**
     * Calculate the code, with given secret and point in time.
     *
     * @param string   $secret
     * @param int|null $timeSlice
     *
     * @return string
     */
    public function getCode($secret, $timeSlice = null)
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / $this->_codeValidSeconds);
        }

        $secretkey = $this->_base32Decode($secret);

        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        // Hash it with users secret key
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        // Use last nipple of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        // grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);

        // Unpak binary value
        $value = unpack('N', $hashpart);
        $value = $value[1];
        // Only 32 bits
        $value = $value & 0x7FFFFFFF;

        $modulo = pow(10, $this->_codeLength);

        return str_pad($value % $modulo, $this->_codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Set the code length, should be >=6.
     *
     * @param int $length
     *
     * @return PHPGangsta_GoogleAuthenticator
     */
    public function setCodeLength($length)
    {
        $this->_codeLength = $length;

        return $this;
    }

    /**
     * Update the Two Factor Settings
     *
     * @param array $settings
     */
    public function updateSettings(array $settings)
    {
        if (isset($settings['codeLength'])) {
            $this->setCodeLength((int)$settings['codeLength']);
        }
        if (isset($settings['codeValidSeconds'])) {
            $this->_codeValidSeconds = (int)$settings['codeValidSeconds'];
        }
        if (isset($settings['secretLength'])) {
            $this->_secretLength = (int)$settings['secretLength'];
        }
        if (isset($settings['verifyDiscrepancy'])) {
            $this->_verifyDiscrepancy = (int)$settings['verifyDiscrepancy'];
        }
    }

    /**
     * Check if the code is correct. This will accept codes starting from $discrepancy*30sec ago to $discrepancy*30sec from now.
     *
     * @param string   $secret
     * @param string   $code
     *
     * @return bool
     */
    public function verify($secret, $code)
    {
        return $this->verifyCode($secret, $code, $this->_verifyDiscrepancy);
    }

    /**
     * Check if the code is correct. This will accept codes starting from $discrepancy*30sec ago to $discrepancy*30sec from now.
     *
     * @param string   $secret
     * @param string   $code
     * @param int      $discrepancy      This is the allowed time drift in 30 second units (8 means 4 minutes before or after)
     * @param int|null $currentTimeSlice time slice if we want use other that time()
     *
     * @return bool
     */
    public function verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null)
    {
        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / $this->_codeValidSeconds);
        }

        if (strlen($code) !== $this->_codeLength) {
            return false;
        }

        // \MUtil_Echo::track($discrepancy);

        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            // \MUtil_Echo::track($i);
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($this->_timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }
}
