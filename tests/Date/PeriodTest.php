<?php

namespace Date;

use Gems\Date\Period;
use Gems\Exception\Coding;
use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase
{
    /**
     * @dataProvider dateTypeDataProvider
     *
     * @return void
     */
    public function testIsDateType($dateType, $isDateType)
    {
        $this->assertEquals($isDateType, Period::isDateType($dateType));
    }

    public static function dateTypeDataProvider()
    {
        return [
          ['S', false],
          ['s', false],
          ['N', false],
          ['H', false],
          ['Moo', true],
          ['0', true],
        ];
    }

    /**
     * @dataProvider applyPeriodDataProvider
     *
     * @return void
     */
    public function testApplyPeriod($applyInput, $expectedResult)
    {
        $this->assertEquals($expectedResult, Period::applyPeriod(...$applyInput));
    }

    public function testInvalidTypeApplyPeriod()
    {
        $this->expectException(Coding::class);
        $this->expectExceptionMessage('Unknown period type: X');
        Period::applyPeriod(new \DateTimeImmutable(), 'X', 10);
    }

    public static function applyPeriodDataProvider()
    {
        return [
          [['startDate' => null, 'type' => 'D', 'period' => 10], null],
          [['startDate' => new \DateTimeImmutable('2023-06-08 00:00:00'), 'type' => 'D', 'period' => 10], new \DateTimeImmutable('2023-06-18 00:00:00')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 00:00:00'), 'type' => 'D', 'period' => -10], new \DateTimeImmutable('2023-05-29 00:00:00')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'S', 'period' => 10], new \DateTimeImmutable('2023-06-08 12:00:10')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'N', 'period' => 1], new \DateTimeImmutable('2023-06-08 12:01:00')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'H', 'period' => 1], new \DateTimeImmutable('2023-06-08 13:00:00')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'S', 'period' => 1], new \DateTimeImmutable('2023-06-08 12:00:01')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'M', 'period' => 1], new \DateTimeImmutable('2023-07-08 00:00:00')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'W', 'period' => 1], new \DateTimeImmutable('2023-06-15 00:00:00')],
          [['startDate' => new \DateTimeImmutable('2023-06-08 12:00:00'), 'type' => 'Y', 'period' => 1], new \DateTimeImmutable('2024-06-08 00:00:00')],
        ];
    }
}