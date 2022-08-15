<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems;

use DateTimeImmutable;
use DateTimeInterface;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Description of RespondentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array              $respondentData
     * @param \DateTimeInterface $date
     * @param boolean            $months
     * @param                    $expected
     * @return void
     * @dataProvider getAgeProvider
     */
    public function testGetAge(array $respondentData, DateTimeInterface $date, \boolean $months, $expected)
    {
        $respondent = new \Gems\Tracker\Respondent(1,1);
        $respondent->answerRegistryRequest('_gemsData', $respondentData);
        
        $actual = $respondent->getAge($date, $months);
        $this->assertEquals($expected, $actual);
    }
    
    public function getAgeProvider()
    {
        $date = new DateTimeImmutable();
        $ageNine = $date->sub(new DateInterval('P9Y-1D'));
        return [
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-15'), true, 120],  // Happy birthday!
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-16'), true, 120],  // The day after
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-04-14'), true, 120],  // Almost a month
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-14'), true, 119],  // Tomorrow
            
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-15'), false, 10],  // Happy birthday!
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-04-14'), false, 10],  // Almost another month
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-04-15'), false, 10],  // One month is nothing
            [['grs_birthday' => new DateTimeImmutable('2000-03-15')], new DateTimeImmutable('2010-03-14'), false, 9],   // One more day
            
            [['grs_birthday' => $ageNine], null, false, 9],
            
            [['grs_birthday' => 5], null, false, null],
        ];
    }
}
