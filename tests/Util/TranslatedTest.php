<?php

namespace Util;

use Carbon\CarbonImmutable;
use Gems\Util\Translated;
use PHPUnit\Framework\TestCase;
use Zalt\Mock\MockTranslator;

class TranslatedTest extends TestCase
{
    /**
     * @dataProvider describeDateFromNowDataProvider
     */
    public function testDescribeDateFromNow($dateTime, $expectedResult): void
    {
        $translated = $this->getTranslated();

        $this->assertEquals($expectedResult, $translated->describeDateFromNow($dateTime));
    }

    public static function describeDateFromNowDataProvider(): array
    {
        $dateTime = new CarbonImmutable();
        return [
            [$dateTime, 'Today'],
            [$dateTime->addDay(), 'Tomorrow'],
            [$dateTime->subDay(), 'Yesterday'],
            [$dateTime->subDays(6), '6 days ago'],
            [$dateTime->addDays(6), '6 days from now'],

            [$dateTime->addDays(15), $dateTime->addDays(15)->format('d-m-Y')],

        ];
    }

    public function getTranslated(): Translated
    {
        return new Translated(new MockTranslator());
    }
}