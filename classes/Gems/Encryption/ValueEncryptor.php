<?php

namespace Gems\Encryption;

class ValueEncryptor
{
    protected array $config = [];

    protected string $salt = '';

    public function __construct(array $config)
    {
        if (isset($this->config['security'], $this->config['security']['encryption'])) {
            $this->config = $config['security']['encryption'];
        }
        if (isset($this->config['app']['key'])) {
            $this->key = $this->config['app']['key'];
        }
    }

    public function decrypt(string $input, ?string $key = null): string
    {
        $methods = $this->getEncryptionMethods();

        $base64 = $input;
        $method = 'AES-256-CBC';
        if (':' == $input[0]) {
            list($empty, $methodKey, $base64) = explode(':', $input, 3);

            if (! isset($methods[$methodKey])) {
                $error = sprintf("Encryption method '%s' not defined in project.ini.", $methodKey);
                throw new \Gems\Exception\Coding($error);
            }

            $method = $methods[$methodKey];
        }
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }

        $decoded = base64_decode($base64);
        $output = $this->decryptOpenSsl($decoded, $method, $key);

        if (false === $output) {
            return $input;
        } else {
            return $output;
        }
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $input String to decrypt
     * @param string $method The cipher method, one of openssl_get_cipher_methods().
     * @param string $key Key to use for decryption
     * @return string decrypted string of false
     */
    protected function decryptOpenSsl(string $input, string $method, string $key): string
    {
        $ivlen = openssl_cipher_iv_length($method);
        $iv    = substr($input, 0, $ivlen);

        return openssl_decrypt(substr($input, $ivlen), $method, $key, 0, $iv);
    }

    public function encrypt(string $input, ?string $key = null): string
    {
        if (! $input) {
            return $input;
        }

        $methods    = $this->getEncryptionMethods();
        $method     = reset($methods);
        $methodKey  = key($methods);
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }

        $result = $this->encryptOpenSsl($input, $method, $key);

        return ":$methodKey:" . base64_encode($result);
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $input String to encrypt
     * @param string $method The cipher method, one of openssl_get_cipher_methods().
     * @param string $key Key used for encryption
     * @return string encrypted string
     */
    protected function encryptOpenSsl(string $input, string $method, string $key): string
    {
        $ivlen = openssl_cipher_iv_length($method);
        $iv    = openssl_random_pseudo_bytes($ivlen);

        return $iv . openssl_encrypt($input, $method, $key, 0, $iv);
    }

    protected function getEncryptionMethods(): array
    {
        if (isset($this->config['methods'])) {
            // reverse so first item is used as default
            return array_reverse($this->config['methods']);
        }
        return ['AES-256-CBC'];
    }

    protected function getEncryptionKey(): string
    {
        return $this->key;
    }
}