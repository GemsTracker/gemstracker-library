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
        $respondent = $this->getMockBuilder('Gems_Tracker_Respondent')
                ->disableOriginalConstructor()
                ->getMock();
        
        $respondent->expects($this->any())
                ->method('getBirthday')
                ->will($this->returnValue(new \MUtil_Date('2000-01-15', 'yyyy-MM-dd')));
        
        $respondent->expects($this->any())
                ->method('getAge')
                ->will($this->returnValue(10));
        
        $token = $this->getMockBuilder('Gems_Tracker_Token')
                ->disableOriginalConstructor()
                ->getMock();
        
        $token->expects($this->any())
                ->method('getRespondent')
                ->will($this->returnValue($respondent));
        
        if ($date) {
            $validFrom = new \MUtil_Date('2010-01-16', 'yyyy-MM-dd');
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
    
}
