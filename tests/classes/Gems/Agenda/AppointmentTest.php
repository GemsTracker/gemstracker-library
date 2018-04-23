<?php

/**
 * Description of AppointmentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class AppointmentTest extends PHPUnit_Framework_TestCase
{

    /**
     * Get a mock for the respondentTrack
     * 
     * @param \MUtil_Date|null $endDate
     *
     * @return \Gems_Tracker_RespondentTrack
     */
    protected function _getRespondentTrack($endDate = null)
    {
        $respTrack = $this->getMockBuilder('Gems_Tracker_RespondentTrack')
                ->disableOriginalConstructor()
                ->getMock();

        $respTrack->expects($this->any())
                ->method('getEndDate')
                ->will($this->returnValue($endDate));

        return $respTrack;
    }

    /**
     * 
     * @dataProvider createAfterWaitDays_NoEndDateProvider
     * @param type $endDate
     * @param type $waitDays
     */
    public function testCreateAfterWaitDays_NoEndDate($expected, $waitDays, $endDate)
    {
        $appointmentDate = new \MUtil_Date('2018-01-01', 'yyyy-MM-dd');

        if ($endDate) {
            $trackEndDate = clone $appointmentDate;
            $trackEndDate->subDay(5);
            $respTrack    = $this->_getRespondentTrack($trackEndDate);
        } else {
            $respTrack = $this->_getRespondentTrack();
        }

        $appointment = new \Gems_Agenda_Appointment([
            'gap_id_appointment' => 1,
            'gap_admission_time' => $appointmentDate
        ]);

        $filter = new \Gems\Agenda\Filter\SubjectAppointmentFilter();
        $filter->exchangeArray([
            'gtap_create_track'     => 1,
            'gtap_id_track'         => 2,
            'gtap_create_wait_days' => $waitDays
        ]);

        $this->assertEquals($expected, $appointment->createAfterWaitDays($filter, $respTrack));
    }

    public function createAfterWaitDays_NoEndDateProvider()
    {
        return [
            [false, 2, false],
            [true, 2, true],
            [false, 5, true],
            [false, 6, true],
        ];
    }

    public function testCreateNever()
    {
        $appointment = new \Gems_Agenda_Appointment(1);
        $filter      = new \Gems\Agenda\Filter\SubjectAppointmentFilter();
        $filter->exchangeArray(['gtap_create_track' => 0]);

        $respTrack = $this->_getRespondentTrack();

        $this->assertEquals(false, $appointment->createNever($filter, $respTrack));
    }

}
