<?php

namespace Gems\Condition;

/**
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class TrackFieldConditionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Gems\Conditions
     */
    public $conditions;

    /**
     *
     * @var \Gems\Condition\Round\TrackFieldCondition
     */
    public $condition;

    public function setUp()
    {
        parent::setUp();

        $this->conditions = new \Gems\Conditions([], ['Gems' => GEMS_ROOT_DIR . '/classes/Gems']);

        $this->condition = $this->conditions->loadRoundCondition('\\Gems\\Condition\\Round\\TrackFieldCondition');
    }

    /**
     * Get a mock for the token object with supplied $fieldData
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getTokenMock($fieldData)
    {
        $respondentTrack = $this->getMockBuilder('Gems_Tracker_RespondentTrack')
                ->disableOriginalConstructor()
                ->getMock();

        $respondentTrack->expects($this->any())
                ->method('getCodeFields')
                ->will($this->returnValue($fieldData));

        $token = $this->getMockBuilder('Gems_Tracker_Token')
                ->disableOriginalConstructor()
                ->getMock();

        $token->expects($this->any())
                ->method('getRespondentTrack')
                ->will($this->returnValue($respondentTrack));

        return $token;
    }

    /**
     *
     * @param type $fields
     * @return \Gems_Tracker_Engine_AnyStepEngine
     */
    protected function _getEngineMock($fieldCodes)
    {
        $trackEngine = $this->getMockBuilder('Gems_Tracker_Engine_AnyStepEngine')
                ->disableOriginalConstructor()
                ->getMock();

        $trackEngine->expects($this->any())
                ->method('getFieldCodes')
                ->will($this->returnValue($fieldCodes));

        return $trackEngine;
    }

    /**
     * When track has the requested field, the condition is valid and can be applied to the round
     */
    public function testFieldExists()
    {
        $config = [
            'gcon_condition_text1' => 'field1',
            'gcon_condition_text2' => \Gems\Conditions::COMPARATOR_EQUALS,
            'gcon_condition_text3' => 5,
            'gcon_condition_text4' => '',
        ];

        $this->condition->exchangeArray($config);

        $fieldCodes = [
            'f__1' => 'test',
            'f__2' => 'field1'
        ];

        $trackEngine = $this->_getEngineMock($fieldCodes);
        $tracker     = $this->getMockBuilder('Gems_Tracker')
                ->disableOriginalConstructor()
                ->getMock();

        $tracker->expects($this->any())
                ->method('getTrackEngine')
                ->will($this->returnValue($trackEngine));

        $this->condition->answerRegistryRequest('tracker', $tracker);

        $valid = $this->condition->isValid(1, ['gro_id_track' => 1]);

        $this->assertTrue($valid);
    }

    /**
     * When track does not have the requested field, the condition is invalid and can not be applied to the round
     */
    public function testFieldNotExists()
    {
        $config = [
            'gcon_condition_text1' => 'field2',
            'gcon_condition_text2' => \Gems\Conditions::COMPARATOR_EQUALS,
            'gcon_condition_text3' => 5,
            'gcon_condition_text4' => '',
        ];

        $this->condition->exchangeArray($config);

        $fieldCodes = [
            'f__1' => 'test',
            'f__2' => 'field1'
        ];

        $trackEngine = $this->_getEngineMock($fieldCodes);
        $tracker     = $this->getMockBuilder('Gems_Tracker')
                ->disableOriginalConstructor()
                ->getMock();

        $tracker->expects($this->any())
                ->method('getTrackEngine')
                ->will($this->returnValue($trackEngine));

        $this->condition->answerRegistryRequest('tracker', $tracker);

        $valid = $this->condition->isValid(1, ['gro_id_track' => 1]);

        $this->assertFalse($valid);
    }

    /**
     * Test the equals operator
     * @dataProvider getRoundValidProvider
     */
    public function testIsRoundValid($config, $fieldData, $expected)
    {
        $this->condition->exchangeArray($config);

        $token = $this->_getTokenMock($fieldData);

        $actual = $this->condition->isRoundValid($token);

        $this->assertEquals($expected, $actual);
    }

    public function getRoundValidProvider()
    {
        return [
            'equals_true'  => [
                [
                    'gcon_condition_text1' => 'field1',
                    'gcon_condition_text2' => \Gems\Conditions::COMPARATOR_EQUALS,
                    'gcon_condition_text3' => '5',
                    'gcon_condition_text4' => '',
                ],
                ['field1' => '5'],
                true
            ],
            'equals_false' => [
                [
                    'gcon_condition_text1' => 'field1',
                    'gcon_condition_text2' => \Gems\Conditions::COMPARATOR_EQUALS,
                    'gcon_condition_text3' => '5',
                    'gcon_condition_text4' => '',
                ],
                ['field1' => '6'],
                false
            ],
            'in_true'      => [
                [
                    'gcon_condition_text1' => 'field1',
                    'gcon_condition_text2' => \Gems\Conditions::COMPARATOR_IN,
                    'gcon_condition_text3' => '5|6|7',
                    'gcon_condition_text4' => '',
                ],
                ['field1' => '5'],
                true
            ],
            'in_false'     => [
                [
                    'gcon_condition_text1' => 'field1',
                    'gcon_condition_text2' => \Gems\Conditions::COMPARATOR_IN,
                    'gcon_condition_text3' => '7|8',
                    'gcon_condition_text4' => '',
                ],
                ['field1' => '6'],
                false
            ]
        ];
    }

}
