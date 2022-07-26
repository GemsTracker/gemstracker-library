<?php


namespace Gems\Condition\Track;

/**
 * Description of AgeConditionTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class AgeConditionTest extends \PHPUnit_Framework_TestCase {
    
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
        
        $this->condition = $this->conditions->loadTrackCondition('\\Gems\\Condition\\Track\\AgeCondition');
    }
    
    /**
     * Get a mock for the token object with a respondent age 10 and a validfrom date when $date is true
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getRespondentTrackMock($ageYears, $trackYears)
    {
        $age = new \MUtil\Date();
        $age->sub($ageYears, 'y');

        $startDate = new \MUtil\Date();
        $startDate->sub($trackYears, 'y');

        $respondent = new \Gems\Tracker\Respondent(1,1);
        $respondentData = ['grs_birthday' => $age];
        $respondent->answerRegistryRequest('_gemsData', $respondentData);

        $respTrack = $this->getMockBuilder('\\Gems\\Tracker\\RespondentTrack')
            ->disableOriginalConstructor()
            ->getMock();

        $respTrack->expects($this->any())
                ->method('getRespondent')
                ->will($this->returnValue($respondent));
        $respTrack->expects($this->any())
            ->method('getStartDate')
            ->will($this->returnValue($startDate));

        return $respTrack;
    }
    
    public function isValid($config, $ageYears, $trackYears)
    {
        $this->condition->exchangeArray($config);

        $respTrack = $this->_getRespondentTrackMock($ageYears, $trackYears);

        return $this->condition->isTrackValid($respTrack);
    }

    public function providerTestAgeNowInvalid()
    {
        return [
            '8 not in 10 - ' => [10, 'Y', '', 8],
            '18 not in 0 - 10' => [0, 'Y', 10, 18],
            '3 not in M 0 - 24' => [0, 'M', 24, 3],
            '3 not in M 0 - 33' => [0, 'M', 35, 3],
        ];
    }

    /**
     * @dataProvider providerTestAgeNowInvalid
     */
    public function testAgeNowInvalid($cond2, $cond3, $cond4, $age)
    {
        $config = [
            'gcon_condition_text1' => 'NOW',
            'gcon_condition_text2' => $cond2,
            'gcon_condition_text3' => $cond3,
            'gcon_condition_text4' => $cond4,
        ];

        $this->assertFalse($this->isValid($config, $age, 0));
    }

    public function providerTestAgeNowValid()
    {
        return [
            '8 in 0 - 10' => [0, 'Y', 10, 8],
            '8 in - 10 - ' => ['', 'Y', 10, 8],
            '18 in 10 - ' => [10, 'Y', '', 18],
            '3 in M 0 - 36' => [0, 'M', 36, 3],
            '3 in M 0 - 37' => [0, 'M', 37, 3],
            ];
    }

    /**
     * @dataProvider providerTestAgeNowValid
     */
    public function testAgeNowValid($cond2, $cond3, $cond4, $age)
    {
        $config = [
            'gcon_condition_text1' => 'NOW',
            'gcon_condition_text2' => $cond2,
            'gcon_condition_text3' => $cond3,
            'gcon_condition_text4' => $cond4,
        ];
        
        $this->assertTrue($this->isValid($config, $age, 0));
    }

    public function testAgeNull()
    {
        $respondent = new \Gems\Tracker\Respondent(1,1);
        $respondentData = ['grs_birthday' => null];
        $respondent->answerRegistryRequest('_gemsData', $respondentData);

        $respTrack = $this->getMockBuilder('\\Gems\\Tracker\\RespondentTrack')
            ->disableOriginalConstructor()
            ->getMock();

        $respTrack->expects($this->any())
            ->method('getRespondent')
            ->will($this->returnValue($respondent));

        $config = [
            'gcon_condition_text1' => 'NOW',
            'gcon_condition_text2' => 1,
            'gcon_condition_text3' => 'Y',
            'gcon_condition_text4' => 10,
        ];
        $this->condition->exchangeArray($config);

        $this->assertFalse($this->condition->isTrackValid($respTrack));
    }

    public function providerTestAgeTrackInvalid()
    {
        return [
            '8 +3 not in 0 - 4' => [0, 'Y', 4, 8, 3],
            '10 -2 not in 0 - 10' => [0, 'Y', 10, 10, -2],
            '18 -2 not in 0 - 10' => [0, 'Y', 10, 18, -2],
        ];
    }

    /**
     * @dataProvider providerTestAgeTrackInvalid
     */
    public function testAgeTrackInvalid($cond2, $cond3, $cond4, $age, $start)
    {
        $config = [
            'gcon_condition_text1' => 'TS',
            'gcon_condition_text2' => $cond2,
            'gcon_condition_text3' => $cond3,
            'gcon_condition_text4' => $cond4,
        ];

        $this->assertFalse($this->isValid($config, $age, $start));
    }

    public function providerTestAgeTrackValid()
    {
        return [
            '8 +2 in 0 - 10' => [0, 'Y', 10, 8, 2],
            '8 -2 in 0 - 6' => [0, 'Y', 6, 8, 2],
        ];
    }

    /**
     * @dataProvider providerTestAgeTrackValid
     */
    public function testAgeTrackValid($cond2, $cond3, $cond4, $age, $start)
    {
        $config = [
            'gcon_condition_text1' => 'TS',
            'gcon_condition_text2' => $cond2,
            'gcon_condition_text3' => $cond3,
            'gcon_condition_text4' => $cond4,
        ];

        $this->assertTrue($this->isValid($config, $age, $start));
    }
}
