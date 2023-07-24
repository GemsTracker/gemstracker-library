<?php

namespace GemsTest\Agenda;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Agenda\Repository\ActivityRepository;
use Gems\Agenda\Repository\LocationRepository;
use Gems\Agenda\Repository\ProcedureRepository;
use Gems\Db\ResultFetcher;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\RespondentTrack;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Description of AppointmentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class AppointmentTest extends TestCase
{

    use ProphecyTrait;

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
        $respTrack = $this->getMockBuilder(RespondentTrack::class)
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

        $appointment = $this->getAppointment([
            'gap_id_appointment' => 1,
            'gap_admission_time' => $appointmentDate
        ]);

        $filter = new \Gems\Agenda\Filter\SubjectAppointmentFilter(
            [
                'gtap_create_track'     => 1,
                'gtap_id_track'         => 2,
                'gtap_create_wait_days' => $waitDays
            ]
        );

        $this->assertEquals($expected, $appointment->createAfterWaitDays($filter, $respTrack));
    }

    public static function createAfterWaitDays_NoEndDateProvider()
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
        $appointmentDate = new DateTimeImmutable('2018-01-01');

        if ($startDate) {
            $trackStartDate = $appointmentDate->sub(new DateInterval('P5D'));
            $respTrack    = $this->_getRespondentTrack(null, $trackStartDate);
        } else {
            $respTrack = $this->_getRespondentTrack();
        }

        $appointment = $this->getAppointment([
            'gap_id_appointment' => 1,
            'gap_admission_time' => $appointmentDate
        ]);

        $filter = new \Gems\Agenda\Filter\SubjectAppointmentFilter([
            'gtap_create_track'     => 1,
            'gtap_id_track'         => 2,
            'gtap_create_wait_days' => $waitDays
        ]);

        $this->assertEquals($expected, $appointment->createFromStart($filter, $respTrack));
    }

    public static function createFromStartProvider()
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
        $appointment = $this->getAppointment([
            'gap_id_appointment' => 1
        ]);
        $filter      = new \Gems\Agenda\Filter\SubjectAppointmentFilter(['gtap_create_track' => 0]);

        $respTrack = $this->_getRespondentTrack();

        $this->assertEquals(false, $appointment->createNever($filter, $respTrack));
    }

    protected function getAppointment(array $appointmentData)
    {
        $translatorProphecy = $this->prophesize(Translator::class);
        $translatorProphecy->trans(Argument::type('string'), Argument::cetera())->willReturnArgument(0);

        $agendaPropecy = $this->prophesize(Agenda::class);

        $resultFetcherProphecy = $this->prophesize(ResultFetcher::class);
        $activityRepositoryProphecy = $this->prophesize(ActivityRepository::class);
        $locationRepositoryProphecy = $this->prophesize(LocationRepository::class);
        $procedureRepositoryProphecy = $this->prophesize(ProcedureRepository::class);
        $respondentRepositoryProphecy = $this->prophesize(RespondentRepository::class);

        return new Appointment(
            $appointmentData,
            $translatorProphecy->reveal(),
            $agendaPropecy->reveal(),
            $activityRepositoryProphecy->reveal(),
            $locationRepositoryProphecy->reveal(),
            $procedureRepositoryProphecy->reveal(),
            $respondentRepositoryProphecy->reveal(),
        );
    }

}
