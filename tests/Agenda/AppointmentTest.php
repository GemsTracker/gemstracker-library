<?php

namespace GemsTest\Agenda;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Description of AppointmentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class AppointmentTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Get a mock for the respondentTrack
     * 
     * @param DateTimeInterface|null $endDate
     * @param DateTimeInterface|null $startDate
     *
     * @return \Gems\Tracker\RespondentTrack
     */
    protected function _getRespondentTrack($endDate = null, $startDate = null)
    {
        $respTrack = $this->getMockBuilder('\\Gems\\Tracker\\RespondentTrack')
                ->disableOriginalConstructor()
                ->getMock();

        $respTrack->expects($this->any())
                ->method('getEndDate')
                ->will($this->returnValue($endDate));
        
        $respTrack->expects($this->any())
                ->method('getStartDate')
                ->will($this->returnValue($startDate));
        

        return $respTrack;
    }

    /**
     * Check createAfterWaitDays method, enddate (if true) will be five days before the appointment.
     * Provide different waitDays to check if the desired result is true of false
     *  
     * @dataProvider createAfterWaitDays_NoEndDateProvider
     * @param DateTimeInterface $waitDays
     * @param DateTimeInterface $endDate
     */
    public function testCreateAfterWaitDays_NoEndDate($expected, $waitDays, $endDate)
    {
        $appointmentDate = new DateTimeImmutable('2018-01-01');

        if ($endDate) {
            $trackEndDate = $appointmentDate->sub(new DateInterval('P5D'));
            $respTrack    = $this->_getRespondentTrack($trackEndDate);
        } else {
            $respTrack = $this->_getRespondentTrack();
        }

        $appointment = new \Gems\Agenda\Appointment([
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
            'noenddate'             => [false, 2, false],
            'enddatebeforewaitdays' => [true, 2, true],
            'enddateonwaitdays'     => [false, 5, true],
            'enddateafterwaitdays'  => [false, 6, true],
        ];
    }
    
    /**
     * Check createFromStart method, startdate (if true) will be five days before the appointment.
     * Provide different waitDays to check if the desired result is true of false
     * 
     * @dataProvider createFromStartProvider
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $waitDays
     */
    public function testCreateFromStart($expected, $waitDays, $startDate)
    {
        $appointmentDate = DateTimeImmutable::createFromFormat('Y-m-d', '2018-01-01');

        if ($startDate) {
            $trackStartDate = $appointmentDate->sub(new DateInterval('P5D'));
            $respTrack    = $this->_getRespondentTrack(null, $trackStartDate);
        } else {
            $respTrack = $this->_getRespondentTrack();
        }

        $appointment = new \Gems\Agenda\Appointment([
            'gap_id_appointment' => 1,
            'gap_admission_time' => $appointmentDate
        ]);

        $filter = new \Gems\Agenda\Filter\SubjectAppointmentFilter();
        $filter->exchangeArray([
            'gtap_create_track'     => 1,
            'gtap_id_track'         => 2,
            'gtap_create_wait_days' => $waitDays
        ]);

        $this->assertEquals($expected, $appointment->createFromStart($filter, $respTrack));
    }

    public function createFromStartProvider()
    {
        return [
            'nostartdate'             => [false, 2, false],
            'startdatebeforewaitdays' => [true, 2, true],
            'startdateonwaitdays'     => [false, 5, true],
            'startdateafterwaitdays'  => [false, 6, true],
        ];
    }

    public function testCreateNever()
    {
        $appointment = new \Gems\Agenda\Appointment(1);
        $filter      = new \Gems\Agenda\Filter\SubjectAppointmentFilter();
        $filter->exchangeArray(['gtap_create_track' => 0]);

        $respTrack = $this->_getRespondentTrack();

        $this->assertEquals(false, $appointment->createNever($filter, $respTrack));
    }

}
