<?php

/**
 * Description of Gems_Tracker_TokenTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class Gems_Tracker_TokenTest extends Gems_Test_DbTestAbstract
{
    /**
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $util = $this->loader->getUtil();
        Zend_Registry::getInstance()->set('util', $util);
        $this->tracker = $this->loader->getTracker();
        
        $this->token = $this->tracker->getToken(array('gto_id_token' => 500));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        //Dataset TokenTest.xml has the minimal data we need to perform our tests
        $classFile =  str_replace('.php', '.xml', __FILE__);
        return $this->createFlatXMLDataSet($classFile);
    }

    /**
     * @covers Gems_Tracker_Token::cacheGet
     * @covers Gems_Tracker_Token::cacheSet
     */
    public function testCacheSetGet()
    {
        $this->token->cacheSet('foo1', 'baz');
        $this->assertEquals('baz', $this->token->cacheGet('foo1'));
    }

    /**
     * @covers Gems_Tracker_Token::cacheHas
     */
    public function testCacheHas()
    {
        $this->assertEquals(null, $this->token->cacheHas('foo2'));
    }

    /**
     * @covers Gems_Tracker_Token::cacheReset
     */
    public function testCacheReset()
    {
        $this->token->cacheSet('foo3', 'baz');
        $this->token->cacheReset();
        $this->assertEquals(null, $this->token->cacheGet('foo3'));
    }

    /**
     * @covers Gems_Tracker_Token::getReceptionCode
     */
    public function testGetReceptionCode()
    {
        $token = $this->tracker->getToken(1);
        
        $receptionCode = $this->loader->getUtil()->getReceptionCode('OK');        
        
        // Remove the following lines when you implement this test.
        $this->assertEquals($receptionCode, $token->getReceptionCode());
    }

    /**
     * @covers Gems_Tracker_Token::getRespondentId
     * @todo   Implement testGetRespondentId().
     */
    public function testGetRespondentId()
    {
        $this->assertNotEmpty($this->tracker->getToken('1')->getRespondentId(), 'Any token should have a respondentId');
        
        try {
            // This one does not exists and should throw an error
            $this->tracker->getToken('2')->getRespondentId();
        } catch (Exception $e) {}
        $this->assertInstanceOf('Gems_Exception', $e, 'Token not loaded correctly');
    }

    /**
     * @covers Gems_Tracker_Token::isCurrentlyValid
     * @dataProvider providerTokenValid
     */
    public function testIsCurrentlyValid($data, $expected)
    {
        $token = $this->tracker->getToken($data);
        $this->assertEquals($expected[2], $token->isCurrentlyValid());
    }
    
    /**
     * @covers Gems_Tracker_Token::isExpired
     * @dataProvider providerTokenValid
     */
    public function testIsExpired($data, $expected)
    {
        $token = $this->tracker->getToken($data);
        $this->assertEquals($expected[0], $token->isExpired());
    }
    
    /**
     * @covers Gems_Tracker_Token::isNotYetValid
     * @dataProvider providerTokenValid
     */
    public function testIsNotYetValid($data, $expected)
    {
        $token = $this->tracker->getToken($data);
        $this->assertEquals($expected[1], $token->isNotYetValid());
    }
    
    public function providerTokenValid() {
        $now = new MUtil_Date();
        $tomorrow = clone $now;
        $yesterday = clone $now;
        $tomorrow->addDay(1);
        $yesterday->subDay(1);
        return array(
            array(
                array(  // Expired
                    'gto_id_token' => '111',
                    'gto_valid_from' => $yesterday,
                    'gto_valid_until' => $yesterday
                    ),
                array(true, false, false)   // Expired, NotYetValid, CurrentlyValid
                ),
            array(  // Current
                array(
                    'gto_id_token' => '111',
                    'gto_valid_from' => $yesterday,
                    'gto_valid_until' => $tomorrow
                ),
                array(false, false, true)
            ),
            array(  // Future
                array(
                    'gto_id_token' => '111',
                    'gto_valid_from' => $tomorrow,
                    'gto_valid_until' => $tomorrow
                ),
                array(false, true, false)
            )
        );
    }

    /**
     * @covers Gems_Tracker_Token::setReceptionCode
     * @todo   Implement testSetReceptionCode().
     */
    public function testSetReceptionCode()
    {
        $token = $this->tracker->getToken(1);
        $receptionCode = 'STOP';
        
        // Create a stub for the survey class, it should be tested on it's own
        $survey = $this->getMockBuilder('Gems_Tracker_Survey')
                      ->disableOriginalConstructor()
                      ->getMock();
        $survey->expects($this->any())
              ->method('inSource')
              ->will($this->returnValue(false));
        
        // Assign survey stub
        $token->answerRegistryRequest('survey', $survey);
        
        $token->setReceptionCode($receptionCode, 'Test', 2);
        $this->assertEquals($receptionCode, $token->getReceptionCode()->getCode());
    }

    /**
     * This tests if the public property exists is set correctly
     *
     * @dataProvider providerTokenData
     */
    public function testTokenExists($tokenData, $exists)
    {
        $token = $this->tracker->getToken($tokenData);
        $this->assertEquals($exists, $token->exists);
    }

    public function providerTokenData()
    {
        return array(
            array(
                '1',
                true),  // Is in the table
            array(
                '2',
                false), // Is not in the table
            array(
                array('gto_id_token' => '111'), // Array provided so not checked and accepted as is
                true)
        );
    }
}
