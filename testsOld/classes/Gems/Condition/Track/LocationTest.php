<?php

/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Condition\Track;

use PHPUnit_Extensions_Database_DataSet_IDataSet;

/**
 *
 * @package    Gem
 * @subpackage Condition\Track
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class LocationTest extends \Gems\Test\DbTestAbstract
{
    /**
     * @var \Gems\Conditions
     */
    public $conditions;

    /**
     *
     * @var \Gems\Condition\Track\AgeCondition
     */
    public $condition;

    /**
     * @var \Gems\Tracker\TrackerInterface
     */
    public $tracker;

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        //Dataset TokenTest.xml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.yml', __FILE__);
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($classFile);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->bootstrap->bootstrap('event');

        $this->conditions = new \Gems\Conditions([], ['Gems' => GEMS_ROOT_DIR . '/classes/Gems']);

        $this->condition  = $this->conditions->loadTrackCondition('\\Gems\\Condition\\Track\\LocationCondition');

        $this->tracker    = $this->loader->getTracker();
    }

    public function isValid($config, $trackId, array $fieldData = null)
    {
        $this->condition->exchangeArray($config);

        $respTrack = $this->tracker->getRespondentTrack($trackId);
        return $this->condition->isTrackValid($respTrack, $fieldData);
    }

    public function providerTestInLoc()
    {
        return [
            '1 at 1' => [1, 1, '', '', ''],
            // '1 at 2' => [1, 2, '', '', ''],
            '1 at 1 code' => [1, 1, '', '', 'code'],
            '2 at 1' => [2, 1, '', '', ''],
            // '2 at 1 code' => [2, 1, '', '', 'code'],
        ];
    }

    /**
     * @dataProvider providerTestInLoc
     */
    public function testInLoc($trackId, $loc1, $loc2, $loc3, $code)
    {
        $config = [
            'gcon_condition_text1' => $loc1,
            'gcon_condition_text2' => $loc2,
            'gcon_condition_text3' => $loc3,
            'gcon_condition_text4' => $code,
        ];

        $this->assertTrue($this->isValid($config, $trackId));
    }

    public function providerTestInLocWithData()
    {
        return [
            '1 at 2' => [1, 2, '', '', '', ['f__1' => 2]],
            '1 at 2 code' => [1, 2,'', '', 'code', ['f__1' => 2]],
            '2 at 1' => [2, 1, '', '', '', ['f__2' => 1]],
            '2 at 3' => [2, 3, '', '', '', ['f__2' => 3]],
        ];
    }

    /**
     * @dataProvider providerTestInLocWithData
     */
    public function testInLocWithData($trackId, $loc1, $loc2, $loc3, $code, $fieldData)
    {
        $config = [
            'gcon_condition_text1' => $loc1,
            'gcon_condition_text2' => $loc2,
            'gcon_condition_text3' => $loc3,
            'gcon_condition_text4' => $code,
        ];

        $this->assertTrue($this->isValid($config, $trackId, $fieldData));
    }
    
    public function providerTestNotInLoc()
    {
        return [
            '1 at 2' => [1, 2, 3, 4, ''],
            '1 at 1 no code' => [1, 1, 2, 3, 'no code'],
            '2 at 1 code' => [2, 1, 2, 3, 'code'],
            '2 at 2' => [2, 2, 3, 4, ''],
            '2 at 3' => [2, '', 3, '', ''],
            '1 at none' => [1, '', '', '', ''],
        ];
    }

    /**
     * @dataProvider providerTestNotInLoc
     */
    public function testNotInLoc($trackId, $loc1, $loc2, $loc3, $code)
    {
        $config = [
            'gcon_condition_text1' => $loc1,
            'gcon_condition_text2' => $loc2,
            'gcon_condition_text3' => $loc3,
            'gcon_condition_text4' => $code,
        ];

        $this->assertFalse($this->isValid($config, $trackId));
    }
    
    public function providerTestNotInLocWithData()
    {
        return [
            '1 at 2' => [1, 1, 3, 4, '', ['f__1' => 2]],
            '1 at 2 no code' => [1, 1, 2, 3, 'no code', ['f__1' => 2]],
            '2 at 1 code' => [2, 4, 2, 3, 'code', ['f__2' => 1]],
            '2 at 1' => [2, 2, 3, 4, '', ['f__2' => 1]],
            '2 at 1' => [2, '', 3, '', '', ['f__2' => 1]],
            '1 at none' => [1, '', '', '', '', ['f__1' => '']],
        ];
    }

    /**
     * @dataProvider providerTestNotInLocWithData
     */
    public function testNotInLocWithData($trackId, $loc1, $loc2, $loc3, $code, $fieldData)
    {
        $config = [
            'gcon_condition_text1' => $loc1,
            'gcon_condition_text2' => $loc2,
            'gcon_condition_text3' => $loc3,
            'gcon_condition_text4' => $code,
        ];

        $this->assertFalse($this->isValid($config, $trackId, $fieldData));
    }
}