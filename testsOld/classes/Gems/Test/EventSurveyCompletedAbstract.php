<?php
abstract class EventSurveyCompletedAbstract extends \PHPUnit_Framework_TestCase {
  
    /**
     * @var \Gems\Event\SurveyCompletedEventInterface
     */
    protected $_score = null;
    
    public function setUp()
    {
        $this->_score = $this->getEventClass();
    }
    
    /**
     * Return the Event to test
     * 
     * @return \Gems\Event\SurveyCompletedEventInterface
     */
    abstract public function getEventClass();

    /**
     * @dataProvider surveyDataProvider
     */
    public function testScore($data, $result)
    {
        // Create a stub for the \Gems\Tracker\Token class.
        $token = $this->getMockBuilder('\\Gems\\Tracker\\Token')
                      ->disableOriginalConstructor()
                      ->getMock();
 
        // Configure the stub.
        $token->expects($this->any())
              ->method('getRawAnswers')
              ->will($this->returnValue($data));
        
        $this->assertEquals($result, $this->_score->processTokenData($token));
    }
    
    /**
     * Should return an array of arrays, containing two elements, first the input tokendata and second the expected output
     * 
     * Example:
     * return array(
     *      array(
     *          array (
     *              'id' => 1,
     *              'submitdate' => '2013-05-28 15:32:40',
     *              'lastpage' => 2,
     *              'startlanguage' => 'nl',
     *              'token' => '4d7v_q544',
     *              'datestamp' => '2013-05-28 15:32:40',
     *              'startdate' => '2013-05-28 15:32:40',
     *              'EAT1' => '2',
     *              'SCORE => null,
     *          ), 
     *          array('SCORE'=>'Abnormal swallowing')),
     *      array(
     *          array (
     *              'id' => 1,
     *              'submitdate' => '2013-05-28 15:32:40',
     *              'lastpage' => 2,
     *              'startlanguage' => 'nl',
     *              'token' => '4d7v_q544',
     *              'datestamp' => '2013-05-28 15:32:40',
     *              'startdate' => '2013-05-28 15:32:40',
     *              'EAT1' => '2',
     *              'SCORE => null,
     *          ), 
     *          array('SCORE'=>'Abnormal swallowing'))
     *      );
     * 
     * @return array
     */
    abstract public function surveyDataProvider();
}