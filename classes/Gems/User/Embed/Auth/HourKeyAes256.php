<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\User\Embed\Auth;

use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\UpdatingAuthInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @since      Class available since version 1.9.2
 */
class HourKeyAes256 extends EmbeddedAuthAbstract implements UpdatingAuthInterface
{
    /**
     * @var array Parameters encrypted in the secret key
     */
    protected $_params = [];

    /**
     * @var bool Show debug code
     */
    protected $debug = false;

    /**
     * Default key to use when no two factor key was set
     *
     * @var string
     */
    protected $defaultKey = 'test';

    /**
     *
     * @var string Algorithm for the PHP hash() function, E.G. sha256
     */
    protected $encryptionAlgorithm = 'AES-256-CBC';

    /**
     *
     * @var boolean When true, apply base64 to encryption output
     */
    protected $encryptionBase64 = true;

    /**
     * Format for date part of key function
     *
     * @var string
     */
    protected $keyTimeFormat = 'YmdH';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    protected $keyTimeValidRange = 1;

    /**
     * Authenticate embedded user
     *
     * @param \Gems_User_User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(\Gems_User_User $user, $secretKey)
    {
        $embedderData = $user->getEmbedderData();
        
        $input = $this->decrypt($secretKey, $this->getEncryptionKey($user));
        
        if ($input) {
            \parse_str($input, $this->_params);
            if (isset($this->_params['chk'])) {
                return in_array($this->_params['chk'], $this->getValidTimeStamps());
            } else {
                return false;
            }
        }
        
        return (boolean) $input;
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $secretKey String to decrypt
     * @param string $encryptionKey Key to use for decryption
     * @return string decrypted string of false
     */
    protected function decrypt($secretKey, $encryptionKey)
    {
        if ($this->encryptionBase64) {
            // Sometimes the plus sign is translated to a space.
            // Using base64 means there should not be a space in the key 
            $secretKey = base64_decode(strtr($secretKey, ' ', '+'));
        }
        
        $ivlen = openssl_cipher_iv_length($this->encryptionAlgorithm);
        $iv    = substr($secretKey, 0, $ivlen);

        return openssl_decrypt(substr($secretKey, $ivlen), $this->encryptionAlgorithm, $encryptionKey, 0, $iv);
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $keyInput String to encrypt
     * @param string $encryptionKey Key used for encryption
     * @return string encrypted string
     */
    protected function encrypt($keyInput, $encryptionKey)
    {
        $ivlen = openssl_cipher_iv_length($this->encryptionAlgorithm);
        $iv    = openssl_random_pseudo_bytes($ivlen);

        $output = $iv . openssl_encrypt($keyInput, $this->encryptionAlgorithm, $encryptionKey, 0, $iv);
        
        if ($this->encryptionBase64) {
            return base64_encode($output);
        } 
        
        return $output;
    }

    public function getEmbeddedParams()
    {
        return $this->_params;
    }

    /**
     * Return the authentication string for the user
     *
     * @param \Gems_User_User $embeddedUser
     * @return string Preferably containing %s
     */
    protected function getEncryptionKey(\Gems_User_User $embeddedUser)
    {
        return $embeddedUser->getSecretKey() ?: $this->defaultKey;
    }

    /**
     *
     * @param \Gems_User_User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(\Gems_User_User $user)
    {
        $url['pid'] = $this->patientNumber;
        // $url['org'] = $this->organizations;
        $url['usr'] = $this->deferredLogin;
        
        $stamps = $this->getValidTimeStamps();
        if (isset($stamps[0])) {
            $url['chk'] = $stamps[0];
        } else {
            $url['chk'] = \end($stamps) ?: 'key';
        }
        
        return $this->encrypt(http_build_query(array_filter($url)), $this->getEncryptionKey($user));
    }

    /**
     * Return the authentication string for the user
     *
     * @param \Gems_User_User $embeddedUser
     * @return string Preferably containing %s
     */
    protected function getKeysStart(\Gems_User_User $embeddedUser)
    {
        $key = $embeddedUser->getSecretKey() ?: $this->defaultKey;

        if (! \MUtil_String::contains($key, '%s')) {
            $key .= '%s';
        }

        return $key;
    }

    public function getLabel()
    {
        $ivlen = openssl_cipher_iv_length($this->encryptionAlgorithm);
        return sprintf($this->_('EAS 256 Ecnrypted Url for EPIC with IV length %d.'), $ivlen);
    }

    /**
     * Generate the \DateInterval constructor
     *
     * @param int $i The "start" interval
     * @return string
     * @throws \Gems_Exception_Coding
     */
    protected function getTimePeriodString($i = 1)
    {
        $timeChar = substr($this->keyTimeFormat, -1);

        switch ($timeChar) {
            case 'o':
            case 'y':
            case 'Y':
                return "P{$i}Y";

            case 'm':
            case 'n':
                return "P{$i}M";

            case 'd':
            case 'j':
                return "P{$i}D";

            case 'H':
            case 'h':
                return "PT{$i}H";

            case 'i':
                return "PT{$i}M";

        }

        throw new \Gems_Exception_Coding("Invalid last keyTimeFormat character '$timeChar' set.");
    }

    /**
     * Return an array of valid key values for this user
     *
     * @return array
     */
    public function getValidTimeStamps()
    {
        $current = new \DateTime();
        $current->sub(new \DateInterval($this->getTimePeriodString($this->keyTimeValidRange)));
        $addDate = new \DateInterval($this->getTimePeriodString(1));
        $keys    = [];

        for ($i = -$this->keyTimeValidRange; $i <= $this->keyTimeValidRange; $i++) {
            $keys[$i] = $current->format($this->keyTimeFormat);
            $current->add($addDate);
        }

        if ($this->debug) {
            \MUtil_Echo::track($keys);
        }
        // \MUtil_Echo::track(hash_algos());

        return $keys;
    }
}