<?php

namespace GemsTest\Tracker\Field;

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Agenda\LaminasAppointmentSelect;
use Gems\Agenda\Repository\ActivityRepository;
use Gems\Agenda\Repository\LocationRepository;
use Gems\Agenda\Repository\ProcedureRepository;
use Gems\Menu\RouteHelper;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Field\AppointmentField;
use Gems\Util\Translated;
use PHPUnit\Framework\TestCase;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlElement;

class AppointmentFieldTest extends TestCase
{
    private function getAppointmentField(
        int $trackId = 1,
        string $fieldKey = 'a__60001',
        array $fieldDefinition = [],
        array $trackData = [
            'gr2t_id_respondent_track' => 1,
        ],
        LaminasAppointmentSelect|null $appointmentSelect = null,
    ): AppointmentField {
        $translator = $this->getTranslator();

        $agenda = $this->createMock(Agenda::class);
        $agenda->method('isStatusActive')->willReturnMap([
            ['AC', true],
            ['CO', true],
            ['AB', false],
            ['CA', false],
        ]);

        $respondentRepository = $this->createMock(RespondentRepository::class);
        $respondentRepository->method('getPatientNr')->with(2, 70)->willReturn('TEST001');

        $agenda->method('getAppointment')
            ->willReturnCallback(function(array|string|int $appointmentData) use ($agenda, $respondentRepository) {
                return match($appointmentData) {
                    1234 => new Appointment(
                        [
                            'gap_id_appointment' => 1234,
                            'gap_status' => 'AC',
                            'gap_admission_time' => '2002-01-15 15:00:00',
                            'gap_id_user' => 1,
                            'gap_id_organization' => 70,
                        ],
                        $this->getTranslator(),
                        $agenda,
                        $this->createMock(ActivityRepository::class),
                        $this->createMock(LocationRepository::class),
                        $this->createMock(ProcedureRepository::class),
                        $this->createMock(RespondentRepository::class),
                    ),
                    5678 => new Appointment(
                        [
                            'gap_id_appointment' => 5678,
                            'gap_status' => 'CA',
                            'gap_admission_time' => '2003-01-01 15:00:00',
                            'gap_id_user' => 2,
                            'gap_id_organization' => 70,
                        ],
                        $this->getTranslator(),
                        $agenda,
                        $this->createMock(ActivityRepository::class),
                        $this->createMock(LocationRepository::class),
                        $this->createMock(ProcedureRepository::class),
                        $respondentRepository,
                    ),
                    9876 => new Appointment(
                        [
                            'gap_id_appointment' => 9876,
                            'gap_status' => 'AC',
                            'gap_admission_time' => '2005-01-15 15:00:00',
                            'gap_id_user' => 1,
                            'gap_id_organization' => 70,
                        ],
                        $this->getTranslator(),
                        $agenda,
                        $this->createMock(ActivityRepository::class),
                        $this->createMock(LocationRepository::class),
                        $this->createMock(ProcedureRepository::class),
                        $this->createMock(RespondentRepository::class),
                    ),
                    default => new Appointment(
                        [
                            'gap_id_appointment' => 1,
                        ],
                        $this->getTranslator(),
                        $agenda,
                        $this->createMock(ActivityRepository::class),
                        $this->createMock(LocationRepository::class),
                        $this->createMock(ProcedureRepository::class),
                        $respondentRepository,
                    )
                };
            });
        if ($appointmentSelect) {
            $agenda->method('createAppointmentSelect')->willReturn($appointmentSelect);
        }

        $routeHelper = $this->createMock(RouteHelper::class);
        $routeHelper->method('getRouteUrl')->willReturnCallback(function(string $name, array $params = []) {
            if (!($name === 'respondent.appointments.show')) {
                return null;
            }
            if (isset($params['id1'], $params['id2'], $params['aid'])) {
                return '/respondent/' . $params['id1'] . '/' . $params['id2'] . '/appointments/' . $params['aid'];
            }
        });

        $appointmentField = new AppointmentField(
            $trackId,
            $fieldKey,
            $fieldDefinition,
            $translator,
            new Translated($translator),
            $agenda,
            $routeHelper,
        );

        $appointmentField->calculationStart($trackData);

        return $appointmentField;
    }

    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('_')->willReturnArgument(0);
        return $translator;
    }

    public function testCalculateFieldInfo(): void
    {
        $appointmentField = $this->getAppointmentField();

        $noValue = $appointmentField->calculateFieldInfo(null, []);
        $this->assertNull($noValue);

        $unknownAppointment = $appointmentField->calculateFieldInfo(999, []);
        $this->assertNull($unknownAppointment);

        $inactiveAppointment = $appointmentField->calculateFieldInfo(5678, []);
        $this->assertNull($inactiveAppointment);

        $activeAppointment = $appointmentField->calculateFieldInfo(1234, []);
        $this->assertEquals('15 Jan 2002 15:00', $activeAppointment);
    }


    public function testCalculateFieldValueNoValue(): void
    {
        $appointmentField = $this->getAppointmentField();

        $value = $appointmentField->calculateFieldValue(null, [], []);

        $this->assertNull($value);

        $value2 = $appointmentField->calculateFieldValue('someValue', [], []);

        $this->assertNotNull($value2);
    }

    public function testFromDateCalculation(): void
    {
        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_diff_target_field' => null,
            ],
        );

        // Diff field not set. (default to track start date)
        $valueFromString = $appointmentField->getFromDate(['gr2t_start_date' => '2000-01-01 15:00:00'], []);
        $this->assertEquals('2000-01-01 00:00:00', $valueFromString->format('Y-m-d H:i:s'));

        // Diff field not set (default to track start date as DateTime Object)
        $valueFromDateTime = $appointmentField->getFromDate(['gr2t_start_date' => new \DateTimeImmutable('2001-01-01 00:00:00')], []);
        $this->assertEquals('2001-01-01 00:00:00', $valueFromDateTime->format('Y-m-d H:i:s'));

        // Diff field not set (default to previous field)
        $appointmentField->setLastActiveAppointmentFromValue(1234);
        $valueFromLastAppointment = $appointmentField->getFromDate(['gr2t_start_date' => '2000-01-01 00:00:00'], ['A__1001' => '9876']);
        $this->assertEquals('2002-01-15 00:00:00', $valueFromLastAppointment->format('Y-m-d H:i:s'));


        // Diff field: specific appointment
        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_diff_target_field' => 'A__1001',
            ],
        );

        $appointmentField->setLastActiveAppointmentFromValue(1234);
        $valueFromLastAppointment = $appointmentField->getFromDate(['gr2t_start_date' => '2000-01-01 00:00:00'], ['A__1001' => 9876]);
        $this->assertEquals('2005-01-15 00:00:00', $valueFromLastAppointment->format('Y-m-d H:i:s'));

        // Diff field: specifically start, with previous field
        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_diff_target_field' => 'start',
            ],
        );

        $appointmentField->setLastActiveAppointmentFromValue(1234);
        $valueFromLastAppointment = $appointmentField->getFromDate(['gr2t_start_date' => '2000-01-01 00:00:00'], ['A__1001' => 9876]);
        $this->assertEquals('2000-01-01 00:00:00', $valueFromLastAppointment->format('Y-m-d H:i:s'));
    }

    public function testCalculationStart(): void
    {
        $appointmentField = $this->getAppointmentField(trackData: []);
        $this->assertNull($appointmentField->getLastActiveKey());

        $appointmentField->calculationStart(['gr2t_id_respondent_track' => 123]);
        $this->assertEquals(123, $appointmentField->getLastActiveKey());

        $appointmentField = $this->getAppointmentField(trackData: []);
        $appointmentField->calculationStart(['gr2t_id_user' => 987, 'gr2t_id_organization' => 70]);
        $this->assertEquals('987__70', $appointmentField->getLastActiveKey());
    }

    public function testShowAppointment(): void
    {
        $appointmentField = $this->getAppointmentField();

        $nullResult = $appointmentField->showAppointment(null);
        $this->assertEquals('Unknown', $nullResult);

        $displayString = $appointmentField->showAppointment(1234);
        $this->assertEquals('15-01-2002 15:00', $displayString);

        $displayUrl = $appointmentField->showAppointment(5678);
        $this->assertInstanceOf(HtmlElement::class, $displayUrl);
        $this->assertEquals('/respondent/TEST001/70/appointments/5678', $displayUrl->getAttrib('href'));
    }

    public function testCalculateFieldValue1DayMinDiff(): void
    {
        $appointmentSelect = $this->createMock(LaminasAppointmentSelect::class);
        $appointmentSelect->expects($this->once())->method('onlyActive')->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forFilterId')->with(1000)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forRespondent')->with(2000, 70)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forPeriod')->with(
            new \DateTimeImmutable('2004-01-16 00:00:00'),
            null,
            true
        )->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('fetchOne')->willReturn(2000001);

        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_filter_id' => 1000,
                'gtf_min_diff_unit' => 'D',
                'gtf_min_diff_length' => '1',
                'gtf_max_diff_exists' => 0,
                'gtf_uniqueness' => 0,
                'gtf_diff_target_field' => null,
            ],
            appointmentSelect: $appointmentSelect
        );

        $result = $appointmentField->calculateFieldValue(123, [], [
            'gr2t_id_user' => 2000,
            'gr2t_id_organization' => 70,
            'gr2t_start_date' => '2004-01-15 00:00:00',
        ]);

        $this->assertEquals(2000001, $result);
    }

    public function testCalculateFieldValueMinus1DayMinDiff(): void
    {
        $appointmentSelect = $this->createMock(LaminasAppointmentSelect::class);
        $appointmentSelect->expects($this->once())->method('onlyActive')->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forFilterId')->with(1000)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forRespondent')->with(2000, 70)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forPeriod')->with(null, new \DateTimeImmutable('2004-01-14 00:00:00'), false)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('fetchOne')->willReturn(2000001);

        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_filter_id' => 1000,
                'gtf_min_diff_unit' => 'D',
                'gtf_min_diff_length' => '-1',
                'gtf_max_diff_exists' => 0,
                'gtf_uniqueness' => 0,
                'gtf_diff_target_field' => null,
            ],
            appointmentSelect: $appointmentSelect);

        $result = $appointmentField->calculateFieldValue(123, [], [
            'gr2t_id_user' => 2000,
            'gr2t_id_organization' => 70,
            'gr2t_start_date' => '2004-01-15 00:00:00',
            'gtf_max_diff_exists' => 0,
        ]);

        $this->assertEquals(2000001, $result);
    }

    public function testCalculateFieldValue1DayMaxDiff(): void
    {
        $appointmentSelect = $this->createMock(LaminasAppointmentSelect::class);
        $appointmentSelect->expects($this->once())->method('onlyActive')->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forFilterId')->with(1000)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forRespondent')->with(2000, 70)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forPeriod')->with(
            new \DateTimeImmutable('2004-01-16 00:00:00'),
            new \DateTimeImmutable('2004-01-17 00:00:00'),
            true
        )->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('fetchOne')->willReturn(2000001);

        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_filter_id' => 1000,
                'gtf_min_diff_unit' => 'D',
                'gtf_min_diff_length' => '1',
                'gtf_max_diff_exists' => 1,
                'gtf_max_diff_unit' => 'D',
                'gtf_max_diff_length' => '2',
                'gtf_uniqueness' => 0,
                'gtf_diff_target_field' => null,
            ],
            appointmentSelect: $appointmentSelect
        );

        $result = $appointmentField->calculateFieldValue(123, [], [
            'gr2t_id_user' => 2000,
            'gr2t_id_organization' => 70,
            'gr2t_start_date' => '2004-01-15 00:00:00',
        ]);

        $this->assertEquals(2000001, $result);
    }

    public function testCalculateFieldValueUniqueInTrack(): void
    {
        $appointmentSelect = $this->createMock(LaminasAppointmentSelect::class);
        $appointmentSelect->expects($this->once())->method('onlyActive')->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forFilterId')->with(1000)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forRespondent')->with(2000, 70)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forPeriod')->with(
            new \DateTimeImmutable('2002-01-16 00:00:00'),
            null,
            true
        )->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('uniqueInTrackInstance')->with([1234 => 1234])->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('fetchOne')->willReturn(2000002);

        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_filter_id' => 1000,
                'gtf_min_diff_unit' => 'D',
                'gtf_min_diff_length' => '1',
                'gtf_uniqueness' => 1,
                'gtf_max_diff_exists' => 0,
                'gtf_diff_target_field' => null,
            ],
            appointmentSelect: $appointmentSelect
        );

        $appointmentField->setLastActiveAppointmentFromValue(1234);

        $result = $appointmentField->calculateFieldValue(123, [], [
            'gr2t_id_user' => 2000,
            'gr2t_id_respondent_track' => 1,
            'gr2t_id_organization' => 70,
            'gr2t_start_date' => '2004-01-15 00:00:00',
        ]);

        $this->assertEquals(2000002, $result);
    }

    public function testCalculateFieldValueUniqueInTrackType(): void
    {
        $appointmentSelect = $this->createMock(LaminasAppointmentSelect::class);
        $appointmentSelect->expects($this->once())->method('onlyActive')->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forFilterId')->with(1000)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forRespondent')->with(2000, 70)->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('forPeriod')->with(
            new \DateTimeImmutable('2002-01-16 00:00:00'),
            null,
            true
        )->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('uniqueForTrackId')->with(1, 1, [1234 => 1234])->willReturnSelf();
        $appointmentSelect->expects($this->once())->method('fetchOne')->willReturn(2000002);

        $appointmentField = $this->getAppointmentField(
            fieldDefinition: [
                'gtf_filter_id' => 1000,
                'gtf_min_diff_unit' => 'D',
                'gtf_min_diff_length' => '1',
                'gtf_uniqueness' => 2,
                'gtf_max_diff_exists' => 0,
                'gtf_diff_target_field' => null,
            ],
            appointmentSelect: $appointmentSelect
        );

        $appointmentField->setLastActiveAppointmentFromValue(1234);

        $result = $appointmentField->calculateFieldValue(123, [], [
            'gr2t_id_user' => 2000,
            'gr2t_id_respondent_track' => 1,
            'gr2t_id_organization' => 70,
            'gr2t_start_date' => '2004-01-15 00:00:00',
        ]);

        $this->assertEquals(2000002, $result);
    }
}