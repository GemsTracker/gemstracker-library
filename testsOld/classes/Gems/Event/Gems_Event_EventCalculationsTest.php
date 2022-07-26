<?php

/**
 * Test class for \Gems\Event\EventCalculations.
 * Generated by PHPUnit on 2011-08-15 at 09:44:58.
 */
namespace Gems\Event;

class EventCalculationsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Gems\Event\EventCalculations
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Gems\Event\EventCalculations();
    }

    public function testAverageInt() {
        $tokenAnswers = array(
                'fld_1'   => 1,
                'fld_2'   => '2',
                'fld_3'   => 1.5, //not integer
                'fld_4'   => 'a', //not integer
                'fld_5'   => 1.4, //not integer
                'fld_6'   => 1.6, //not integer
                '1_fld_7' => 6,   //for fieldname test
                '1_fld_8' => -1,   //for fieldname test
                'notme'   => 2    //for fieldname test
            );

        //Initial check (1+2 / 2)
        $this->assertEquals(1.5, $this->object->averageInt($tokenAnswers, array('fld_1', 'fld_2')));

        //Check if non-int will be left out (1+2 / 2)
        $this->assertEquals(1.5, $this->object->averageInt($tokenAnswers, array('fld_1', 'fld_2', 'fld_3', 'fld_4')));

        //Supply a string to test if the right fields get added (1+2+6-1 / 4)
        $this->assertEquals(2, $this->object->averageInt($tokenAnswers, 'fld'));
    }

    public function testReverseCode1() {
        $this->assertEquals(10, $this->object->reverseCode(1, 1, 10));
        $this->assertEquals(9, $this->object->reverseCode(2, 1, 10));
        $this->assertEquals(6, $this->object->reverseCode(5, 1, 10));

        $this->assertEquals(5, $this->object->reverseCode(0, 0, 5));
        $this->assertEquals(3, $this->object->reverseCode(2, 0, 5));
        $this->assertEquals(0, $this->object->reverseCode(5, 0, 5));
    }

    public function testSumInt() {
        $tokenAnswers = array(
                'fld_1'   => 1,
                'fld_2'   => '2',
                'fld_3'   => 1.5, //not integer
                'fld_4'   => 'a', //not integer
                'fld_5'   => 1.4, //not integer
                'fld_6'   => 1.6, //not integer
                '1_fld_7' => 2,   //for fieldname test
                'notme'   => 2    //for fieldname test
            );

        //Initial check
        $this->assertEquals(3, $this->object->sumInt($tokenAnswers, array('fld_1', 'fld_2')));
        
        //Check if non-int will be left out
        $this->assertEquals(3, $this->object->sumInt($tokenAnswers, array('fld_1', 'fld_2', 'fld_3')));
        $this->assertEquals(3, $this->object->sumInt($tokenAnswers, array('fld_1', 'fld_2', 'fld_4')));

        //Make sure there are no rounding issues
        $this->assertEquals(3, $this->object->sumInt($tokenAnswers, array('fld_1', 'fld_2', 'fld_5')));
        $this->assertEquals(3, $this->object->sumInt($tokenAnswers, array('fld_1', 'fld_2', 'fld_6')));

        //Supply a string to test if the right fields get added
        $this->assertEquals(5, $this->object->sumInt($tokenAnswers, 'fld'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }
}