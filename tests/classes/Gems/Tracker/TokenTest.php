<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * Test class for Gems_Tracker_Token
 *
 * @package    Gems
 * @subpackage Test
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Test class for Gems_Tracker_Token
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
     * @covers Gems_Tracker_Token::applyToMenuSource
     * @todo   Implement testApplyToMenuSource().
     */
    public function testApplyToMenuSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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
     * @covers Gems_Tracker_Token::checkRegistryRequestsAnswers
     * @todo   Implement testCheckRegistryRequestsAnswers().
     */
    public function testCheckRegistryRequestsAnswers()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::checkTokenCompletion
     * @todo   Implement testCheckTokenCompletion().
     */
    public function testCheckTokenCompletion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::createReplacement
     * @todo   Implement testCreateReplacement().
     */
    public function testCreateReplacement()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getAnswerDateTime
     * @todo   Implement testGetAnswerDateTime().
     */
    public function testGetAnswerDateTime()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getAnswerSnippetNames
     * @todo   Implement testGetAnswerSnippetNames().
     */
    public function testGetAnswerSnippetNames()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getChangedBy
     * @todo   Implement testGetChangedBy().
     */
    public function testGetChangedBy()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getCompletionTime
     * @todo   Implement testGetCompletionTime().
     */
    public function testGetCompletionTime()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getConsentCode
     * @todo   Implement testGetConsentCode().
     */
    public function testGetConsentCode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getDateTime
     * @todo   Implement testGetDateTime().
     */
    public function testGetDateTime()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getDeleteSnippetNames
     * @todo   Implement testGetDeleteSnippetNames().
     */
    public function testGetDeleteSnippetNames()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getEditSnippetNames
     * @todo   Implement testGetEditSnippetNames().
     */
    public function testGetEditSnippetNames()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getModel
     * @todo   Implement testGetModel().
     */
    public function testGetModel()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getNextToken
     * @todo   Implement testGetNextToken().
     */
    public function testGetNextToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getNextUnansweredToken
     * @todo   Implement testGetNextUnansweredToken().
     */
    public function testGetNextUnansweredToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getOrganization
     * @todo   Implement testGetOrganization().
     */
    public function testGetOrganization()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getOrganizationId
     * @todo   Implement testGetOrganizationId().
     */
    public function testGetOrganizationId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getPatientNumber
     * @todo   Implement testGetPatientNumber().
     */
    public function testGetPatientNumber()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getPreviousSuccessToken
     * @todo   Implement testGetPreviousSuccessToken().
     */
    public function testGetPreviousSuccessToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getPreviousToken
     * @todo   Implement testGetPreviousToken().
     */
    public function testGetPreviousToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRawAnswers
     * @todo   Implement testGetRawAnswers().
     */
    public function testGetRawAnswers()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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
     * @covers Gems_Tracker_Token::getRespondentGender
     * @todo   Implement testGetRespondentGender().
     */
    public function testGetRespondentGender()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRespondentGenderHello
     * @todo   Implement testGetRespondentGenderHello().
     */
    public function testGetRespondentGenderHello()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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
     * @covers Gems_Tracker_Token::getRespondentLastName
     * @todo   Implement testGetRespondentLastName().
     */
    public function testGetRespondentLastName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRespondentName
     * @todo   Implement testGetRespondentName().
     */
    public function testGetRespondentName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRespondentTrack
     * @todo   Implement testGetRespondentTrack().
     */
    public function testGetRespondentTrack()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRespondentTrackId
     * @todo   Implement testGetRespondentTrackId().
     */
    public function testGetRespondentTrackId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getReturnUrl
     * @todo   Implement testGetReturnUrl().
     */
    public function testGetReturnUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRoundDescription
     * @todo   Implement testGetRoundDescription().
     */
    public function testGetRoundDescription()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRoundId
     * @todo   Implement testGetRoundId().
     */
    public function testGetRoundId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getRoundOrder
     * @todo   Implement testGetRoundOrder().
     */
    public function testGetRoundOrder()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getShowSnippetNames
     * @todo   Implement testGetShowSnippetNames().
     */
    public function testGetShowSnippetNames()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getStatus
     * @todo   Implement testGetStatus().
     */
    public function testGetStatus()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getSurvey
     * @todo   Implement testGetSurvey().
     */
    public function testGetSurvey()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getSurveyId
     * @todo   Implement testGetSurveyId().
     */
    public function testGetSurveyId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getSurveyAnswerModel
     * @todo   Implement testGetSurveyAnswerModel().
     */
    public function testGetSurveyAnswerModel()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getSurveyName
     * @todo   Implement testGetSurveyName().
     */
    public function testGetSurveyName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getTokenCountUnanswered
     * @todo   Implement testGetTokenCountUnanswered().
     */
    public function testGetTokenCountUnanswered()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getTokenId
     * @todo   Implement testGetTokenId().
     */
    public function testGetTokenId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getTrackEngine
     * @todo   Implement testGetTrackEngine().
     */
    public function testGetTrackEngine()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getTrackId
     * @todo   Implement testGetTrackId().
     */
    public function testGetTrackId()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getUrl
     * @todo   Implement testGetUrl().
     */
    public function testGetUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getValidFrom
     * @todo   Implement testGetValidFrom().
     */
    public function testGetValidFrom()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::getValidUntil
     * @todo   Implement testGetValidUntil().
     */
    public function testGetValidUntil()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::hasAnswersLoaded
     * @todo   Implement testHasAnswersLoaded().
     */
    public function testHasAnswersLoaded()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::hasResult
     * @todo   Implement testHasResult().
     */
    public function testHasResult()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::handleAfterCompletion
     * @todo   Implement testHandleAfterCompletion().
     */
    public function testHandleAfterCompletion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::handleBeforeAnswering
     * @todo   Implement testHandleBeforeAnswering().
     */
    public function testHandleBeforeAnswering()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::hasRedoCode
     * @todo   Implement testHasRedoCode().
     */
    public function testHasRedoCode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::hasRedoCopyCode
     * @todo   Implement testHasRedoCopyCode().
     */
    public function testHasRedoCopyCode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::hasSuccesCode
     * @todo   Implement testHasSuccesCode().
     */
    public function testHasSuccesCode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::inSource
     * @todo   Implement testInSource().
     */
    public function testInSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::isCompleted
     * @todo   Implement testIsCompleted().
     */
    public function testIsCompleted()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::refresh
     * @todo   Implement testRefresh().
     */
    public function testRefresh()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::setNextToken
     * @todo   Implement testSetNextToken().
     */
    public function testSetNextToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Gems_Tracker_Token::setRawAnswers
     * @todo   Implement testSetRawAnswers().
     */
    public function testSetRawAnswers()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
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
     * @covers Gems_Tracker_Token::setValidFrom
     * @todo   Implement testSetValidFrom().
     */
    public function testSetValidFrom()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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
