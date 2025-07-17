<?php

namespace Gems\User\Embed\Auth;

use PHPUnit\Framework\TestCase;
use Gems\User\User;
use Gems\User\Embed\EmbeddedUserData;
use Zalt\Mock\MockTranslator;

class TimeKeySha256 extends TimeKeySha256Abstract
{
    public function getLabel(): string
    {
        return 'TimeKeySha256';
    }
}

class TimeKeySha256AbstractTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testGetTimePeriodString(string $keyTimeFormat, int $keyTimeValidRange, string $time, array $expected)
    {
        $translator = new MockTranslator();
        $authClass = new TimeKeySha256($translator);
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
        $keys = $authClass->getValidKeys($user, $embeddedUserData, $time);

        $this->assertEquals($expected, $keys);
    }

    public static function dataProvider()
    {
        return [
            // echo -n testkey-20130809 | openssl dgst -binary -sha256 | openssl base64
            ['Ymd', 0, '2013-08-09 11:12:13', [
                0 => '3DXQsSl1/KKeo4iS1k5UR9SfE/DDikluSfuOi5Fg0FQ=',
            ]],
            // echo -n testkey-2013080911 | openssl dgst -binary -sha256 | openssl base64
            ['YmdH', 1, '2013-08-09 11:12:13', [
                0 => '+Y/D/RgSxdF4L93iaqJTNkGPYVqEdtbWjoJav6E+86Y=',
                1 => 'c+aemb9ZTuuNeMJCGlQ0kRfifDxe+zQOkTQf4HlGk5w=',
                -1 => 'XZYh8ejFGc5Odtwhq6CGi2d+/4bvNLMrvbvcq7ZIySA=',
            ]],
            // echo -n testkey-201308091112 | openssl dgst -binary -sha256 | openssl base64
            ['YmdHi', 2, '2013-08-09 11:12:13', [
                0 => 'Ysx2k4+RJQ7mYHs2KiTlLIb30qjxywTPkZp5f4dJinM=',
                1 => 'qXxt98SqrCm5dZ5A6Q2bxtTi4gzpHCLW0yd2yEc4ONg=',
                2 => 'eWWVRrTlYNHu4ssvEYIRruNluWBJhMHKnP2Y0+J92Xo=',
                -1 => 'Pl+Rz1r6qD+XnoqTxNfJcZ+SPb007izVEy3TAdpBYIA=',
                -2 => 'pekUybdFRT2WGM/dVYAtZQQSQ5MPrcqjHKpjKvTxLf4=',
            ]],
        ];
    }
}
