<?php
                
/**
 *
 * @package    Gem
 * @subpackage Condition
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Condition\Track;

/**
 *
 * @package    Gem
 * @subpackage Condition
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class OrganizationTest extends \PHPUnit_Framework_TestCase
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

    public function setUp() {
        parent::setUp();

        $this->conditions = new \Gems\Conditions([], ['Gems' => GEMS_ROOT_DIR . '/classes/Gems']);

        $this->condition = $this->conditions->loadTrackCondition('\\Gems\\Condition\\Track\\OrganizationCondition');
    }

    /**
     * Get a mock for the token object with a respondent age 10 and a validfrom date when $date is true
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getRespondentTrackMock($orgId)
    {
        $respTrack = $this->getMockBuilder('\\Gems\\Tracker\\RespondentTrack')
            ->disableOriginalConstructor()
            ->getMock();

        $respTrack->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue($orgId));

        return $respTrack;
    }

    public function isValid($config, $orgId) {
        $this->condition->exchangeArray($config);

        $respTrack = $this->_getRespondentTrackMock($orgId);

        $valid = $this->condition->isTrackValid($respTrack);

        return $valid;
    }

    public function providerTesInOrg()
    {
        return [
            '1 at 1' => [1, 1, '', '', ''],
            '1 at 2' => [1, '', 1,'', ''],
            '1 at 3' => [1, '', '', 1, ''],
            '1 at 4' => [1, '', '', '', 1],
            '1 in 4' => [1, 5, 6, 7, 1],
        ];
    }

    /**
     * @dataProvider providerTesInOrg
     */
    public function testInOrg($target, $org1, $org2, $org3, $org4)
    {
        $config = [
            'gcon_condition_text1' => $org1,
            'gcon_condition_text2' => $org2,
            'gcon_condition_text3' => $org3,
            'gcon_condition_text4' => $org4,
        ];

        $this->assertTrue($this->isValid($config, $target));
    }

    public function providerTesNotInOrg()
    {
        return [
            '1 not 2-5' => [1, 2, 3, 4, 5],
            '1 not 2-3' => [1, 2, 3, '', ''],
            '1 not 3' => [1, '', 3, '', ''],
            '1 not nothing' => [1, '', '', '', ''],
        ];
    }

    /**
     * @dataProvider providerTesNotInOrg
     */
    public function testNotInOrg($target, $org1, $org2, $org3, $org4)
    {
        $config = [
            'gcon_condition_text1' => $org1,
            'gcon_condition_text2' => $org2,
            'gcon_condition_text3' => $org3,
            'gcon_condition_text4' => $org4,
        ];

        $this->assertFalse($this->isValid($config, $target));
    }
}