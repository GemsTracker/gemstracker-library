<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems;

/**
 * Description of RespondentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * 
     * @param type $respondentData
     * @param type $expected
     * @dataProvider getAgeProvider
     */
    public function testGetAge($respondentData, $date, $months, $expected)
    {
        $respondent = new \Gems\Tracker\Respondent(1,1);
        $respondent->answerRegistryRequest('_gemsData', $respondentData);
        
        $actual = $respondent->getAge($date, $months);
        $this->assertEquals($expected, $actual);
    }
    
    public function getAgeProvider()
    {
        $ageNine = new \MUtil\Date();
        $ageNine->sub(10, 'y');
        $ageNine->addDay(1);
        return [
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-03-15', 'yyyy-MM-dd'), true, 120],  // Happy birthday!
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-03-16', 'yyyy-MM-dd'), true, 120],  // The day after
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-04-14', 'yyyy-MM-dd'), true, 120],  // Almost a month
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-03-14', 'yyyy-MM-dd'), true, 119],  // Tomorrow
            
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-03-15', 'yyyy-MM-dd'), false, 10],  // Happy birthday!
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-04-14', 'yyyy-MM-dd'), false, 10],  // Almost another month
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-04-15', 'yyyy-MM-dd'), false, 10],  // One month is nothing
            [['grs_birthday' => new \MUtil\Date('2000-03-15', 'yyyy-MM-dd')], new \MUtil\Date('2010-03-14', 'yyyy-MM-dd'), false, 9],   // One more day
            
            [['grs_birthday' => $ageNine], null, false, 9],
            
            [['grs_birthday' => 5], null, false, null],
        ];
    }
}
