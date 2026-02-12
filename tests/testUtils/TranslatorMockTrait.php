<?php

namespace GemsTest\testUtils;

use Zalt\Base\TranslatorInterface;

trait TranslatorMockTrait
{
    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('_')->willReturnArgument(0);
        return $translator;
    }
}