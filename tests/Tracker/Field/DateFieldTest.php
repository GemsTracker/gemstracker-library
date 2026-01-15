<?php

namespace GemsTest\Tracker\Field;

use DateTimeImmutable;
use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Tracker\Field\DateField;
use Gems\Util\Translated;
use PHPUnit\Framework\TestCase;
use Zalt\Base\TranslatorInterface;

class DateFieldTest extends TestCase
{
    private function getDateField(
        int $trackId = 1,
        string $fieldKey = 'a__60001',
        array $fieldDefinition = [],
        array $appointmentInput = []
    ): DateField
    {


        return new DateField($trackId, $fieldKey, $fieldDefinition, $this->getTranslator(), $this->getTranslatedUtil(), $this->getAgenda($appointmentInput));
    }

    private function getAgenda(array $input): Agenda
    {
        $agenda = $this->createMock(Agenda::class);

        $agenda->method('getAppointment')->willReturnCallback(function($appointmentId) use ($input) {
            if (!isset($input[$appointmentId])) {
                return null;
            }
            $appointmentInfo = $input[$appointmentId];
            if (!is_array($appointmentInfo)) {
                $appointmentInfo = [
                    'admissionTime' => $appointmentInfo,
                    'active' => true,
                ];
            }
            $appointment = $this->createMock(Appointment::class);
            $appointment->method('isActive')->willReturn($appointmentInfo['active'] ?? true);
            $appointment->method('getAdmissionTime')->willReturn($appointmentInfo['admissionTime']);

            return $appointment;
        });

        return $agenda;
    }

    private function getTranslatedUtil(): Translated
    {
        $util = $this->createMock(Translated::class);
        $util->method('getDateCalculationOptions')->willReturn([0 => 'Calculated', 1 => 'Manually']);
        return $util;
    }
    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('_')->willReturnArgument(0);
        return $translator;
    }

    public function testNonCaluculatingNull(): void
    {
        $field = $this->getDateField();

        $result = $field->calculateFieldValue(null, [], []);
        $this->assertNull($result);
    }

    public function testNonCaluculatingDateTimeValue(): void
    {
        $field = $this->getDateField();

        $date = new DateTimeImmutable();

        $result = $field->calculateFieldValue($date, [], []);
        $this->assertEquals($date, $result);
    }

    public function testNonCaluculatingDateTimeString(): void
    {
        $field = $this->getDateField();

        $date = '2026-01-14 12:34:56';

        $expected = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date);

        $result = $field->calculateFieldValue($date, [], []);
        $this->assertEquals($expected, $result);
    }

    public function testExistingAppointment(): void
    {
        $fieldKey = 'a__60001';

        $field = $this->getDateField(
            fieldKey: $fieldKey,
            fieldDefinition: [
                'gtf_calculate_using' => $fieldKey,
            ],
            appointmentInput: [
                '123' => new DateTimeImmutable('2022-01-14 12:34:56'),
            ],
        );

        $result = $field->calculateFieldValue(null, [
            $fieldKey => 123,
        ], []);

        $this->assertEquals(new DateTimeImmutable('2022-01-14 12:34:56'), $result);
    }

    public function testExistingAppointmentWithPreviousValue(): void
    {
        $fieldKey = 'a__60001';

        $field = $this->getDateField(
            fieldKey: $fieldKey,
            fieldDefinition: [
                'gtf_calculate_using' => $fieldKey,
            ],
            appointmentInput: [
                '123' => new DateTimeImmutable('2022-01-14 12:34:56'),
            ],
        );

        $result = $field->calculateFieldValue(new DateTimeImmutable('2023-01-14 12:34:56'), [
            $fieldKey => 123,
        ], []);

        $this->assertEquals(new DateTimeImmutable('2022-01-14 12:34:56'), $result);
    }

    public function testExistingMultipleAppointments(): void
    {
        $fieldKey = 'a__60001';
        $fieldKey2 = 'a__60002';

        $field = $this->getDateField(
            fieldKey: $fieldKey,
            fieldDefinition: [
                'gtf_calculate_using' => $fieldKey . '|' . $fieldKey2,
            ],
            appointmentInput: [
                '123' => new DateTimeImmutable('2022-01-14 12:34:56'),
                '456' => new DateTimeImmutable('2023-01-14 12:34:56'),
            ],
        );

        $result = $field->calculateFieldValue(null, [
            $fieldKey => 123,
            $fieldKey2 => 456,
        ], []);

        $this->assertEquals(new DateTimeImmutable('2023-01-14 12:34:56'), $result);
    }

    public function testExistingMultipleInactiveAppointments(): void
    {
        $fieldKey = 'a__60001';
        $fieldKey2 = 'a__60002';

        $field = $this->getDateField(
            fieldKey: $fieldKey,
            fieldDefinition: [
                'gtf_calculate_using' => $fieldKey . '|' . $fieldKey2,
            ],
            appointmentInput: [
                '123' => ['admissionTime' => new DateTimeImmutable('2022-01-14 12:34:56'), 'active' => false],
                '456' => ['admissionTime' => new DateTimeImmutable('2023-01-14 12:34:56'), 'active' => false],
            ],
        );

        $result = $field->calculateFieldValue(null, [
            $fieldKey => 123,
            $fieldKey2 => 456,
        ], []);

        $this->assertEquals(null, $result);
    }

    public function testExistingFirstInactiveAppointments(): void
    {
        $fieldKey = 'a__60001';
        $fieldKey2 = 'a__60002';

        $field = $this->getDateField(
            fieldKey: $fieldKey,
            fieldDefinition: [
                'gtf_calculate_using' => $fieldKey . '|' . $fieldKey2,
            ],
            appointmentInput: [
                '123' => ['admissionTime' => new DateTimeImmutable('2022-01-14 12:34:56'), 'active' => false],
                '456' => ['admissionTime' => new DateTimeImmutable('2023-01-14 12:34:56'), 'active' => true],
            ],
        );

        $result = $field->calculateFieldValue(null, [
            $fieldKey => 123,
            $fieldKey2 => 456,
        ], []);

        $this->assertEquals(new DateTimeImmutable('2023-01-14 12:34:56'), $result);
    }

    public function testNullAppointmentFieldWithExistingValue(): void
    {
        $fieldKey = 'a__60001';

        $field = $this->getDateField(
            fieldKey: $fieldKey,
            fieldDefinition: [
                'gtf_calculate_using' => $fieldKey,
            ],
            appointmentInput: [
                '123' => new DateTimeImmutable('2022-01-14 12:34:56'),
            ],
        );

        $result = $field->calculateFieldValue(new DateTimeImmutable('2023-01-14 12:34:56'), [
            $fieldKey => null,
        ], []);

        $this->assertNull($result);
    }
}