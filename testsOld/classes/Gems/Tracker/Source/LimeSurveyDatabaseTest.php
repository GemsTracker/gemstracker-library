<?php

namespace Gems\Tracker\Source;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;


class LimeSurveyDatabaseTest extends \Gems\Test\DbTestAbstract
{
    /**
     *
     * @var \Gems\Tracker\Source\LimeSurvey1m9Database
     */
    protected $object;
    public function setUp()
    {
        parent::setUp();
        $sourceData = [];
        $this->object = new \Gems\Tracker\Source\LimeSurvey1m9Database($sourceData, $this->db);
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
     * @param DateTimeInterface|null $fromDate
     * @param DateTimeInterface|null $untilDate
     * @param [] $expected
     * @dataProvider validDatesProvider
     */
    public function testValidDates($fromDate, $untilDate, $expected)
    {
        $token = $this->getMockBuilder('\\Gems\\Tracker\\Token')
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
        $now      = new DateTimeImmutable('today');
        $weekDiff = new DateInterval('P7D');
        $nextWeek = $now->add($weekDiff);
        $lastWeek = $now->sub($weekDiff);
        $tomorrow = new DateTimeImmutable('tomorrow');;

        $nextWeekUntil = $nextWeek->setTime(23,59,59);
        return [            
            'futureOpen' => [
                $nextWeek, 
                null,
                [
                    'validfrom'  => $nextWeek->format('Y-m-d H:i:s'),
                    'validuntil' => $now->format('Y-m-d 23:59:59'),
                ]                                
            ],
            'futureClosed' => [
                $tomorrow,
                $nextWeekUntil,
                [
                    'validfrom'  => $tomorrow->format('Y-m-d H:i:s'),
                    'validuntil' => $nextWeek->format('Y-m-d 23:59:59'),
                ]                                
            ],
            'open' => [
                $lastWeek,
                $nextWeekUntil,
                [
                    'validfrom'  => $lastWeek->format('Y-m-d H:i:s'),
                    'validuntil' => $nextWeek->format('Y-m-d 23:59:59'),
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
                    'validfrom'  => $lastWeek->format('Y-m-d H:i:s'),
                    'validuntil' => $lastWeek->format('Y-m-d H:i:s'),
                ]                                
            ],
        ];
    }

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet([]);        
    }

}
