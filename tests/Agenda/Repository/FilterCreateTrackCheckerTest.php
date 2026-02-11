<?php

namespace Agenda\Repository;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Agenda\Filter\SubjectAppointmentFilter;
use Gems\Agenda\Filter\TrackFieldFilterCalculation;
use Gems\Agenda\Repository\ActivityRepository;
use Gems\Agenda\Repository\FilterCreateTrackChecker;
use Gems\Agenda\Repository\LocationRepository;
use Gems\Agenda\Repository\ProcedureRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\RespondentTrack;
use GemsTest\testUtils\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Zalt\Mock\MockTranslator;

/**
 * Description of AppointmentTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class FilterCreateTrackCheckerTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * Check createAfterWaitDays method, enddate (if true) will be five days before the appointment.
     * Provide different waitDays to check if the desired result is true of false
     *  
     * @dataProvider createAfterWaitDays_NoEndDateProvider
     */
    public function testCreateAfterWaitDays(bool $expected, int $waitDays, bool $hasEndDate)
    {
        $agenda = $this->getAgenda();
        $appointmentDate = new DateTimeImmutable('2018-01-01');

        if ($hasEndDate) {
            $trackEndDate = $appointmentDate->sub(new DateInterval('P5D'));
            $respTrack    = $this->_getRespondentTrack($trackEndDate);
        } else {
            $respTrack = $this->_getRespondentTrack();
        }

        $appointment = $this->getAppointment([
            'gap_id_appointment' => 1,
            'gap_admission_time' => $appointmentDate
        ]);

        $checker = $this->getChecker();

        $appointmentFilter = new SubjectAppointmentFilter(1, 'createTestFilter', 10, true, null, null, null, null, null);

        $filter = new TrackFieldFilterCalculation(1,2, $appointmentFilter, 1, $waitDays);

        $this->assertEquals($expected, $checker->createAfterWaitDays($agenda, $appointment, $filter, $respTrack));
    }

    public static function createAfterWaitDays_NoEndDateProvider(): array
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
     */
    public function testCreateFromStart(bool $expected, int $waitDays, bool $hasStartDate): void
    {
        $agenda = $this->getAgenda();
        $appointmentDate = new DateTimeImmutable('2018-01-01');

        if ($hasStartDate) {
            $trackStartDate = $appointmentDate->sub(new DateInterval('P5D'));
            $respTrack    = $this->_getRespondentTrack(null, $trackStartDate);
        } else {
            $respTrack = $this->_getRespondentTrack();
        }

        $appointment = $this->getAppointment([
            'gap_id_appointment' => 1,
            'gap_admission_time' => $appointmentDate
        ]);

        $checker = $this->getChecker();

        $appointmentFilter = new SubjectAppointmentFilter(1, 'createTestFilter', 10, true, null, null, null, null, null);

        $filter = new TrackFieldFilterCalculation(1,2, $appointmentFilter, 1, $waitDays);


        $this->assertEquals($expected, $checker->createFromStart($agenda, $appointment, $filter, $respTrack));
    }

    public static function createFromStartProvider(): array
    {
        return [
            'nostartdate'             => [false, 2, false],
            'startdatebeforewaitdays' => [true, 2, true],
            'startdateonwaitdays'     => [false, 5, true],
            'startdateafterwaitdays'  => [false, 6, true],
        ];
    }

    public function testCreateNever(): void
    {
        $agenda = $this->getAgenda();
        $appointment = $this->getAppointment([
            'gap_id_appointment' => 1
        ]);
        $checker = $this->getChecker();

        $appointmentFilter = new SubjectAppointmentFilter(1, 'createTestFilter', 10, true, null, null, null, null, null);

        $filter = new TrackFieldFilterCalculation(1,2, $appointmentFilter, 0, 180);

        $respTrack = $this->_getRespondentTrack();

        $this->assertEquals(false, $checker->createNever($agenda, $appointment, $filter, $respTrack));
    }

    /**
     * @dataProvider createFromCurrentFieldProvider
     */
    public function testCreateFromCurrentField(bool $expected, int $waitDays, bool $hasPreviousAppointmentDate, bool $hasCurrentAppointmentDate = true, bool $sameAppointment = false): void
    {
        $agenda = $this->getAgenda();

        $appointmentDate = new DateTimeImmutable('2026-01-20');
        if (!$hasCurrentAppointmentDate) {
            $appointmentDate = null;
        }

        $respondentTrack = $this->createMock(RespondentTrack::class);

        $fieldData = [];
        if ($hasPreviousAppointmentDate && $appointmentDate) {
            $previousFieldDate = $appointmentDate->sub(new DateInterval('P5D'));
            $previousAppointment = $this->getAppointment([
                'gap_id_appointment' => 122,
                'gap_admission_time' => $previousFieldDate,
            ]);
            $fieldData = [
                'a__1' => 122,
            ];
            $agenda->method('getAppointment')->willReturn($previousAppointment);
        }
        $respondentTrack->method('getFieldData')->willReturn($fieldData);

        $appointment = $this->getAppointment([
            'gap_id_appointment' => 123,
            'gap_admission_time' => $appointmentDate
        ]);
        if ($sameAppointment && $previousAppointment) {
            $appointment = $previousAppointment;
        }

        $checker = $this->getChecker();

        $appointmentFilter = new SubjectAppointmentFilter(1001, 'createTestFilter', 10, true, null, null, null, null, null);

        $filter = new TrackFieldFilterCalculation(1,2, $appointmentFilter, 1, $waitDays);

        $this->assertEquals($expected, $checker->createFromCurrentField($agenda, $appointment, $filter, $respondentTrack));
    }

    public static function createFromCurrentFieldProvider(): array
    {
        return [
            'noPreviousAppointmentDate'     => [true, 2, false, true],
            'noCurrentAppointmentDate'      => [false, 2, true, false],
            'appointmentDateBeforeWaitDays' => [true, 2, true, true],
            'appointmentDateOnWaitDays'     => [false, 5, true, true],
            'appointmentDateAfterWaitDays'  => [false, 6, true, true],
            'alreadyAssignedAppointment'   => [false, 6, true, true, true],
        ];
    }

    protected function getAgenda(): Agenda
    {
        return $this->createMock(Agenda::class);
    }

    protected function getAppointment(array $appointmentData): Appointment
    {
        $agenda = $this->getAgenda();

        $activityRepository = $this->createMock(ActivityRepository::class);
        $locationRepository = $this->createMock(LocationRepository::class);
        $procedureRepository = $this->createMock(ProcedureRepository::class);
        $respondentRepository = $this->createMock(RespondentRepository::class);

        return new Appointment(
            $appointmentData,
            $this->getTranslator(),
            $agenda,
            $activityRepository,
            $locationRepository,
            $procedureRepository,
            $respondentRepository,
        );
    }

    protected function getChecker(): FilterCreateTrackChecker
    {
        return new FilterCreateTrackChecker(new MockTranslator());
    }

    /**
     * Get a mock for the respondentTrack
     *
     * @param DateTimeInterface|null $endDate
     * @param DateTimeInterface|null $startDate
     *
     * @return RespondentTrack
     */
    protected function _getRespondentTrack(DateTimeInterface|null $endDate = null, DateTimeInterface|null $startDate = null): RespondentTrack
    {
        $respondentTrack = $this->createMock(RespondentTrack::class);
        $respondentTrack->method('getEndDate')->willReturn($endDate);
        $respondentTrack->method('getStartDate')->willReturn($startDate);
        $respondentTrack->method('getFieldData')->willReturn([]);

        return $respondentTrack;
    }

}
