<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gems\Event\Survey\BeforeAnswering;

/**
 * Description of PrefillAnswersTest
 *
 * @author 175780
 */
class FillTrackFieldAnswersTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var \Gems_Event_SurveyBeforeAnsweringEventInterface
     */
    public $event;

    public function setUp()
    {
        $this->event = new FillTrackFieldAnswers();
    }

    /**
     * @dataProvider surveyDataProvider
     */
    public function testScore($data, $trackFields, $trackFieldsRaw, $respondentData, $result)
    {
        // Create a stub for the \Gems_Tracker_Token class.
        $token = $this->getMockBuilder('Gems_Tracker_Token')
                ->disableOriginalConstructor()
                ->getMock();

        $survey = $this->getMockBuilder('Gems_Tracker_Survey')
                ->disableOriginalConstructor()
                ->getMock();

        $respondentTrack = $this->getMockBuilder('Gems_Tracker_RespondentTrack')
                ->disableOriginalConstructor()
                ->getMock();

        $respondent = $this->getMockBuilder('Gems_Tracker_Respondent')
                ->disableOriginalConstructor()
                ->getMock();
        
        $receptionCode = $this->getMockBuilder('Gems_Util_ReceptionCode')
                ->disableOriginalConstructor()
                ->getMock();
        
        $receptionCode->expects($this->any())
                ->method('isSuccess')
                ->will($this->returnValue(true));

        $respondent->expects($this->any())
                ->method('getGender')
                ->will($this->returnValue($respondentData['Sex']));

        $respondent->expects($this->any())
                ->method('getBirthday')
                ->will($this->returnValue(new \MUtil_Date($respondentData['Dob'], 'yyyy-MM-dd')));

        $survey->expects($this->any())
                ->method('getQuestionList')
                ->will($this->returnValue($data));

        // Configure the stub.
        $token->expects($this->any())
                ->method('getSurvey')
                ->will($this->returnValue($survey));
        $token->expects($this->any())
                ->method('getReceptionCode')
                ->will($this->returnValue($receptionCode));
        $token->expects($this->any())
                ->method('isCompleted')
                ->will($this->returnValue(false));
        $token->expects($this->any())
                ->method('getRespondentTrack')
                ->will($this->returnValue($respondentTrack));
        $token->expects($this->any())
                ->method('getRespondent')
                ->will($this->returnValue($respondent));

        $respondentTrack->expects($this->any())
                ->method('getCodeFields')
                ->will($this->returnValue($trackFields));
        
        $respondentTrack->expects($this->any())
                ->method('getFieldData')
                ->will($this->returnValue($trackFieldsRaw));

        $this->assertEquals($result, $this->event->processTokenInsertion($token));
    }

    public function surveyDataProvider()
    {
        return [
            [// Token
                [
                    'TFTest' => null,
                    'Test'  => null,
                    'RDBirthDate'  => null,
                    
                ],
                // TrackFields
                [
                    'TEst' => 'waarde'
                ],
                // Raw track fields
                [
                    'TEst' => 'waarde'
                ],
                // RespondentFields
                [
                    'Sex' => 'M',
                    'Dob' => new \MUtil_Date('15-12-2008', 'dd-MM-yyyy')
                ],
                // Result
                [
                    'Test'  => 'waarde',
                ]
            ]
        ];
    }

}
