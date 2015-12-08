<?php
class BMITest extends \Gems_Test_EventSurveyCompletedAbstract {

    /**
     * Return the Event to test
     * 
     * @return \Gems_Event_SurveyCompletedEventInterface
     */
    public function getEventClass() {
        return new \Gems_Event_Survey_Completed_BmiCalculation();
    }

    /**
     * Should return an array of arrays, containing two elements, first the input tokendata and second the expected output
     * 
     * @return array
     */
    public function surveyDataProvider() {
        return array(
            array(
                array(
                    'id'            => 1,
                    'submitdate'    => '2013-05-28 15:32:40',
                    'lastpage'      => 2,
                    'startlanguage' => 'nl',
                    'token'         => '4d7v_q544',
                    'datestamp'     => '2013-05-28 15:32:40',
                    'startdate'     => '2013-05-28 15:32:40',
                    'LENGTH'        => 185,
                    'WEIGHT'        => 78,
                    'BMI'           => null
                ),
                array('BMI' => 22.79)),
            array(
                array(
                    'id'            => 1,
                    'submitdate'    => '2013-05-28 15:32:40',
                    'lastpage'      => 2,
                    'startlanguage' => 'nl',
                    'token'         => '4d7v_q544',
                    'datestamp'     => '2013-05-28 15:32:40',
                    'startdate'     => '2013-05-28 15:32:40',
                    'LENGTH'        => 165,
                    'WEIGHT'        => 70,
                    'BMI'           => null
                ),
                array('BMI' => 25.71))
        );
    }

}