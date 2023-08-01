<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GemsTest\Tracker;

use Gems\Db\ResultFetcher;
use Gems\Tracker\Token\TokenLibrary;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Description of TokenLibraryTest
 *
 * @author mdekk
 */
class TokenLibraryTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testConfig = [
        'tokens' => [
            'chars'  => '23456789abcdefghijklmnopqrstuvwxyz',
            'format' => 'XXXX\-XXXX',
            'from' => '01',
            'to' => 'ol',
        ],
    ];

    /**
     *
     * @var \Gems\Tracker\Token\TokenLibrary
     */
    protected $tokenLibrary;

    protected function setUp(): void
    {
        parent::setUp();

        $resultFetcherProphecy = $this->prophesize(ResultFetcher::class);
        $tokenLibrary = new TokenLibrary(
            $resultFetcherProphecy->reveal(),
            $this->testConfig
        );

        $this->tokenLibrary = $tokenLibrary;
    }

    /**
     * Test the token filter
     * 
     * @dataProvider filterProvider
     */
    public function testFilter($token, $expected) {
        $this->assertEquals($expected, $this->tokenLibrary->filter($token));
    }

    public static function filterProvider() {
        return [
            ['ABCD-EFGH', 'abcd-efgh'], // Check to lowercase
            ['0bcd-efgh', 'obcd-efgh'], // Check replacement zero to o
            ['1bcd-efgh', 'lbcd-efgh'], // Check replacement 1 to l
            ['abcd-efgh', 'abcd-efgh'], // Check replacement 1 to l
            ['abcd-efghi', 'abcd-efgh ?i'], // Check to long
            ['abcd-efg', 'abcd-efg'], // Check to short
            ['abcdefgh', 'abcd-efgh'], // Check omit dash
            ['abcd_efgh', 'abcd-efgh'], // Check invalid char instead of dash
            ['.abcd_efgh', 'abcd-efgh'], // Check invalid char at start of token
            ['ab.cd_efgh', 'abcd-efgh'], // Check invalid char before end of token but not at dash
            ['abcd_efgh.', 'abcd-efgh'], // Check invalid char at end of token
            ["\tabcd_efgh", 'abcd-efgh'], // Check tab in front of token
        ];
    }

}
