<?php

namespace Util;

use Carbon\CarbonImmutable;
use Gems\Util\Translated;
use MUtil\Translate\Translator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class TranslatedTest extends TestCase
{
    use ProphecyTrait;

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
        $translatorProphecy = $this->prophesize(Translator::class);
        $translatorProphecy->trans(Argument::type('string'), Argument::cetera())->willReturnArgument(0);
        $translatorProphecy->getLocale()->willReturn('en');

        $translator = $translatorProphecy->reveal();
        //$translatorProphecy->_(Argument::type('string'))->willReturnArgument();
        return new Translated($translator);
    }
}