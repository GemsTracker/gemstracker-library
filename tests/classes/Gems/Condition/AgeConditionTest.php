<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Condition;

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
     * @var \Gems\Condition\Round\AgeCondition
     */
    public $condition;
    
    public function setUp() {
        parent::setUp();
        
        $this->conditions = new \Gems\Conditions([], ['Gems' => GEMS_ROOT_DIR . '/classes/Gems']);
        
        $this->condition = $this->conditions->loadRoundCondition('\\Gems\\Condition\\Round\\AgeCondition');
    }
    
    /**
     * Get a mock for the token object with a respondent age 10 and a validfrom date when $date is true
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getTokenMock($date = true)
    {
        $ageTen = new \MUtil_Date();
        $ageTen->sub(10, 'y');
        $respondent = new \Gems_Tracker_Respondent(1,1);
        $respondentData = ['grs_birthday' => $ageTen];
        $respondent->answerRegistryRequest('_gemsData', $respondentData);
                
        $token = $this->getMockBuilder('Gems_Tracker_Token')
                ->disableOriginalConstructor()
                ->getMock();
        
        $token->expects($this->any())
                ->method('getRespondent')
                ->will($this->returnValue($respondent));
        
        if ($date) {
            $validFrom = new \MUtil_Date();
        } else {
            $validFrom = null;
        }
        $token->expects($this->any())
                ->method('getValidFrom')
                ->will($this->returnValue($validFrom));
        
        return $token;
    }
    
    public function isValid($config, $date = true) {
        $this->condition->exchangeArray($config);
        
        $token = $this->_getTokenMock($date);
        
        $valid = $this->condition->isRoundValid($token);
        
        return $valid;
    }
    
    /**
     * 10 is before the range 11-20 so should be false
     */
    public function testAgeBefore()
    {
        $config = [
            'gcon_condition_text1' => 11,
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 20,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertFalse($this->isValid($config));
    }
    
    /**
     * 10 is exactly the min age, so should be true
     */
    public function testAgeIsMin()
    {
        $config = [
            'gcon_condition_text1' => 10,
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 20,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertTrue($this->isValid($config));
    }
    
    /**
     * 10 is in the range 5-20 so should be true
     */
    public function testAgeInRange()
    {
        $config = [
            'gcon_condition_text1' => 5,
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 20,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertTrue($this->isValid($config));
    }
    
    /**
     * 10 is exactly the max age, so should be true
     */
    public function testAgeIsMax()
    {
        $config = [
            'gcon_condition_text1' => 5,
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 10,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertTrue($this->isValid($config));
    }
    
    /**
     * 10 is after the range 5-9 so should be false
     */
    public function testAgeAfter()
    {
        $config = [
            'gcon_condition_text1' => 5,
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 9,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertFalse($this->isValid($config));
    }
    
    /**
     * There is no valid from date, so age can not b determined, we keep the 
     * condition valid for now
     */
    public function testNoValidFrom()
    {
        $config = [
            'gcon_condition_text1' => 5,
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 9,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertTrue($this->isValid($config, false));
    }
    
    public function testOnlyMaxAge()
    {
        $config = [
            'gcon_condition_text1' => '',
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => 20,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertTrue($this->isValid($config));
    }
    
    public function testOnlyMinAge()
    {
        $config = [
            'gcon_condition_text1' => '11',
            'gcon_condition_text2' => '',
            'gcon_condition_text3' => '',
            'gcon_condition_text4' => '',
        ];
        
        $this->assertFalse($this->isValid($config));
    }
    
    public function testMonthsOk()
    {
        $config = [
            'gcon_condition_text1' => 100,
            'gcon_condition_text2' => 'M',
            'gcon_condition_text3' => 200,
            'gcon_condition_text4' => '',
        ];
        
        $this->assertTrue($this->isValid($config));
    }
}
