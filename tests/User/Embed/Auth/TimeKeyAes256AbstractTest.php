<?php

namespace Gems\User\Embed\Auth;

use PHPUnit\Framework\TestCase;
use Gems\User\User;
use Gems\User\Embed\EmbeddedUserData;
use Zalt\Mock\MockTranslator;

class TimeKeyAes256 extends TimeKeyAes256Abstract
{
    public function getLabel(): string
    {
        return 'TimeKeyAes256';
    }
}

class TimeKeyAes256AbstractTest extends TestCase
{
    /**
     * @dataProvider timeDataProvider
     */
    public function testGetTimePeriodString(string $keyTimeFormat, int $keyTimeValidRange, string $time, array $expected)
    {
        $translator = new MockTranslator();
        $authClass = new TimeKeyAes256($translator);
        $ref = new \ReflectionClass($authClass);
        $formatProp = $ref->getProperty('keyTimeFormat');
        $formatProp->setAccessible(true);
        $formatProp->setValue($authClass, $keyTimeFormat);
        $rangeProp = $ref->getProperty('keyTimeValidRange');
        $rangeProp->setAccessible(true);
        $rangeProp->setValue($authClass, $keyTimeValidRange);
        $user = $this->createMock(User::class);
        $embeddedUserData = $this->createMock(EmbeddedUserData::class);
        $embeddedUserData->method('getSecretKey')->willReturn('testkey-%s');
        $keys = $authClass->getValidTimestamps($time);

        $this->assertEquals($expected, $keys);
    }

    public static function timeDataProvider()
    {
        return [
            // echo -n testkey-20130809 | openssl dgst -binary -sha256 | openssl base64
            ['Ymd', 0, '2013-08-09 11:12:13', [
                0 => '20130809',
            ]],
            // echo -n testkey-2013080911 | openssl dgst -binary -sha256 | openssl base64
            ['YmdH', 1, '2013-08-09 11:12:13', [
                0 => '2013080911',
                1 => '2013080912',
                -1 => '2013080910',
            ]],
            // echo -n testkey-201308091112 | openssl dgst -binary -sha256 | openssl base64
            ['YmdHi', 2, '2013-08-09 11:12:13', [
                0 => '201308091112',
                1 => '201308091113',
                2 => '201308091114',
                -1 => '201308091111',
                -2 => '201308091110',
            ]],
        ];
    }

    public function testGetIvLengthReturnsCorrectLength()
    {
        $translator = new MockTranslator();
        $authClass = new TimeKeyAes256($translator);

        $expected = openssl_cipher_iv_length('AES-256-CBC');
        $this->assertEquals($expected, $authClass->getIvLength());
    }

    public static function encryptionDataProvider()
    {
        return [
            ['foo', 'bar'],
        ];
    }

    /**
     * @dataProvider encryptionDataProvider
     */
    public function testEncryptDecryptString(string $input, string $key)
    {
        $translator = new MockTranslator();
        $authClass = new TimeKeyAes256($translator);
        $ref = new \ReflectionClass($authClass);
        $encryptMethod = $ref->getMethod('encrypt');
        $encryptMethod->setAccessible(true);
        $decryptMethod = $ref->getMethod('decrypt');
        $decryptMethod->setAccessible(true);
        $encrypted = $encryptMethod->invoke($authClass, $input, $key);
        $decrypted = $decryptMethod->invoke($authClass, $encrypted, $key);

        $this->assertEquals($input, $decrypted);
    }

    public static function decryptionDataProvider()
    {
        return [
            ['AHdrkbYeV8urgjIYbVA1X0VacnNMYTRvSVhNaHdMdzViaHpydmc9PQ==', 'bar', 'foo'],
        ];
    }

    /**
     * @dataProvider decryptionDataProvider
     */
    public function testDecryptEncodedString(string $input, string $key, string $expected)
    {
        $translator = new MockTranslator();
        $authClass = new TimeKeyAes256($translator);
        $ref = new \ReflectionClass($authClass);
        $decodeMethod = $ref->getMethod('decode');
        $decodeMethod->setAccessible(true);
        $decryptMethod = $ref->getMethod('decrypt');
        $decryptMethod->setAccessible(true);
        $decoded = $decodeMethod->invoke($authClass, $input);
        $decrypted = $decryptMethod->invoke($authClass, $decoded, $key);

        $this->assertEquals($expected, $decrypted);
    }
}
