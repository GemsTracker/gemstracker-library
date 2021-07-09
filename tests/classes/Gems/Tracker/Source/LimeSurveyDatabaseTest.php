<?php

namespace Gems\Tracker\Source;

class LimeSurveyDatabaseTest extends \Gems_Test_DbTestAbstract
{
    /**
     *
     * @var \Gems_Tracker_Source_LimeSurvey1m9Database
     */
    protected $object;
    public function setUp()
    {
        parent::setUp();
        $sourceData = [];
        $this->object = new \Gems_Tracker_Source_LimeSurvey1m9Database($sourceData, $this->db);
    }
    
    public function filterKeys($a)
    {
        $out = [];
        foreach($a as $key => $value)
        {
            $new = str_replace('_', '', $key);
            // Prevent accidentally overwriting
            if (!array_key_exists($new, $a)) {
                $out[$new] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        
        return $out;
    }
    
    /**
     * 
     * @param \MUtil_Date|null $fromDate
     * @param \MUtil_Date|null $untilDate
     * @param [] $expected
     * @dataProvider validDatesProvider
     */
    public function testValidDates($fromDate, $untilDate, $expected)
    {
        $token = $this->getMockBuilder('Gems_Tracker_Token')
                ->disableOriginalConstructor()
                ->getMock();
                
        $token->expects($this->any())
                ->method('getValidFrom')
                ->will($this->returnValue($fromDate));
        
        $token->expects($this->any())
                ->method('getValidUntil')
                ->will($this->returnValue($untilDate));
        
        $actual = $this->object->getValidDates($token);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * There is a risk that the time attribute will not match due to the second changing
     * Address that if the test fails but leave for now
     * 
     * @return []
     */
    public function validDatesProvider()
    {
        $now = new \MUtil_Date();
        $now->setTimeToDayStart();
        $nextWeek = clone $now;
        $nextWeek->addDay(7);
        $lastWeek = clone $now;
        $lastWeek->subDay(7);
        $tomorrow = clone $now;
        $tomorrow->addDay(1);

        $nextWeekUntil = clone $nextWeek;
        $nextWeekUntil->setTimeToDayEnd();
        return [            
            'futureOpen' => [
                $nextWeek, 
                null,
                [
                    'validfrom'  => $nextWeek->toString('yyyy-MM-dd HH:mm:ss'),
                    'validuntil' => $now->toString('yyyy-MM-dd 23:59:59'),
                ]                                
            ],
            'futureClosed' => [
                $tomorrow,
                $nextWeekUntil,
                [
                    'validfrom'  => $tomorrow->toString('yyyy-MM-dd HH:mm:ss'),
                    'validuntil' => $nextWeek->toString('yyyy-MM-dd 23:59:59'),
                ]                                
            ],
            'open' => [
                $lastWeek,
                $nextWeekUntil,
                [
                    'validfrom'  => $lastWeek->toString('yyyy-MM-dd HH:mm:ss'),
                    'validuntil' => $nextWeek->toString('yyyy-MM-dd 23:59:59'),
                ]                                
            ],
            'unknown' => [
                null, 
                null, 
                [
                    'validfrom'  => '1900-01-01 00:00:00',
                    'validuntil' => '1900-01-01 00:00:00',
                ]                                
            ],
            'onlyend' => [
                null, 
                $nextWeek, 
                [
                    'validfrom'  => '1900-01-01 00:00:00',
                    'validuntil' => '1900-01-01 00:00:00',
                ]                                
            ],
            'past' => [
                $lastWeek, 
                $lastWeek, 
                [
                    'validfrom'  => $lastWeek->toString('yyyy-MM-dd HH:mm:ss'),
                    'validuntil' => $lastWeek->toString('yyyy-MM-dd HH:mm:ss'),
                ]                                
            ],
        ];
    }

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet([]);        
    }

}
