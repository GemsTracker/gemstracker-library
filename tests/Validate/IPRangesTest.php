<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GemsTest\Validate;

/**
 * Description of IPRangesTest
 *
 * @author 175780
 */
class IPRangesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new \Gems\Validate\IPRanges();
    }

    /**
     *
     * @dataProvider validProvider
     */
    public function testValid($range)
    {
        $this->assertEquals(true, $this->validator->isValid($range));
    }

    public static function validProvider()
    {
        return [
            ['10.0.0.0'],
            ['10.0.0.0-10.0.0.100'],
            ['10.0.0.0/30'],
            ['10.0.0.*'],
            ['10.0.*.*'],
            ['10.*.*.*'],
            ['*.*.*.*'],
            ['10.0.0.0|10.0.0.0/30|*.*.*.*'],
        ];
    }

    /**
     *
     * @dataProvider invalidProvider
     */
    public function testInvalid($range)
    {
        $this->assertEquals(false, $this->validator->isValid($range));
    }

    public static function invalidProvider()
    {
        return [
            ['10.0.0.0.1'],          // One digit too much
            ['10.0.0.0/33'],         // Out of range (max = 32)
            ['10.0.*.1'],            // Can only end in asterisk
            ['10.0.*.*-10.1.*.*'],   // No 'double range' -> use / notation
            ['10.*.*.*|10.0.0.0.1'], // One ok, one not ok
        ];
    }

}
