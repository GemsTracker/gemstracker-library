<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Tracker;

/**
 * Description of TokenLibraryTest
 *
 * @author mdekk
 */
class TokenLibraryTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     * @var \Gems_Tracker_Token_TokenLibrary
     */
    protected $object;

    public function setUp() {
        parent::setUp();

        $object = new \Gems_Tracker_Token_TokenLibrary();

        $projectArray = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/project.example.ini', APPLICATION_ENV);
        $project = new \Gems_Project_ProjectSettings($projectArray);
        $object->answerRegistryRequest('project', $project);
        $object->checkRegistryRequestsAnswers();

        $this->object = $object;
    }

    /**
     * Test the token filter
     * 
     * @dataProvider filterProvider
     */
    public function testFilter($token, $expected) {
        $this->assertEquals($expected, $this->object->filter($token));
    }

    public function filterProvider() {
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
