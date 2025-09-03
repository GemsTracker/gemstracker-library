<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\User\Embed\Auth;

use Gems\Exception\Coding;
use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\User;
use MUtil\EchoOut\EchoOut;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @since      Class available since version v2.0.54
 */
abstract class TimeKeyAes256Abstract extends EmbeddedAuthAbstract
{
    /**
     * @var array Parameters encrypted in the secret key
     */
    protected array $_params = [];

    /**
     * @var bool Show debug code
     */
    protected bool $debug = false;

    /**
     * Default key to use when no two factor key was set
     *
     * @var string
     */
    protected string $defaultKey = 'test';

    /**
     *
     * @var string Algorithm for the encryption function
     */
    protected string $encryptionAlgorithm = 'AES-256-CBC';

    /**
     *
     * @var boolean When true, apply base64 to encryption output
     */
    protected bool $encryptionBase64 = true;

    /**
     * Format for date part of key function
     *
     * @var string
     */
    protected string $keyTimeFormat = 'YmdH';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    protected int $keyTimeValidRange = 0;

    /**
     * Authenticate embedded user
     *
     * @param User $user
     * @param EmbeddedUserData $embeddedUserData
     * @param string $secretKey
     * @return bool
     */
    public function authenticate(User $user, EmbeddedUserData $embeddedUserData, string $secretKey): bool
    {
        $input = $this->decrypt($this->decode($secretKey), $this->getEncryptionKey($embeddedUserData));
        if ($input == false) {
            return false;
        }
        parse_str($input, $this->_params);
        if (!isset($this->_params['chk'])) {
            return false;
        }
        return in_array($this->_params['chk'], $this->getValidTimeStamps());
    }

    /**
     * Apply base64 encoding if enabled
     */
    protected function encode(string $str): string
    {
        if ($this->encryptionBase64) {
            return base64_encode($str);
        }

        return $str;
    }

    /**
     * Apply base64 decoding if enabled
     */
    protected function decode(string $str): string
    {
        if ($this->encryptionBase64) {
            // Sometimes the plus sign is translated to a space.
            // Using base64 means there should not be a space in the key 
            return base64_decode(strtr($str, ' ', '+'));
        }

        return $str;
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $secretKey String to decrypt
     * @param string $encryptionKey Key to use for decryption
     * @return string decrypted string or false
     */
    protected function decrypt($secretKey, $encryptionKey): string|false
    {
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
    protected function encrypt($keyInput, $encryptionKey): string
    {
        $ivlen = openssl_cipher_iv_length($this->encryptionAlgorithm);
        $iv    = openssl_random_pseudo_bytes($ivlen);

        return $iv . openssl_encrypt($keyInput, $this->encryptionAlgorithm, $encryptionKey, 0, $iv);
    }

    public function getEmbeddedParams()
    {
        return $this->_params;
    }

    /**
     * Return the authentication string for the user
     *
     * @param EmbeddedUserData $embeddedUserData
     * @return string Preferably containing %s
     */
    protected function getEncryptionKey(EmbeddedUserData $embeddedUserData): string
    {
        return $embeddedUserData->getSecretKey() ?: $this->defaultKey;
    }

    /**
     *
     * @param User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(User $user, EmbeddedUserData $embeddedUserData): string
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
        
        return $this->encode($this->encrypt(http_build_query(array_filter($url)), $this->getEncryptionKey($embeddedUserData)));
    }

    /**
     * @return string Something to display as label.
     */
    abstract public function getLabel(): string;

    public function getIvLength(): int
    {
        return openssl_cipher_iv_length($this->encryptionAlgorithm);
    }

    /**
     * Generate the \DateInterval constructor
     *
     * @param int $i The "start" interval
     * @return string
     * @throws \Gems\Exception\Coding
     */
    protected function getTimePeriodString($i = 1): string
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

        throw new Coding("Invalid last keyTimeFormat character '$timeChar' set.");
    }

    /**
     * Return an array of valid timestamps
     *
     * @return string[]
     */
    public function getValidTimeStamps(string $datetime = 'now'): array
    {
        $current = new \DateTime($datetime);
        $current->sub(new \DateInterval($this->getTimePeriodString($this->keyTimeValidRange)));
        $addDate = new \DateInterval($this->getTimePeriodString(1));
        $keys    = [];

        for ($i = -$this->keyTimeValidRange; $i <= $this->keyTimeValidRange; $i++) {
            $keys[$i] = $current->format($this->keyTimeFormat);
            $current->add($addDate);
        }

        if ($this->debug) {
           EchoOut::track($keys);
        }
        // \MUtil_Echo::track(hash_algos());

        return $keys;
    }
}
