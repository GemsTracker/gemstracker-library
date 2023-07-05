<?php

namespace GemsTest\MenuNew;

use Gems\Config\Menu;
use Gems\Event\Application\MenuBuildItemsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testTranslatesLabels()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->will($this->returnCallback(fn($x) => 'TRANS' . $x));

        $menu = new Menu($translator);

        $this->assertStringStartsWith('TRANS', $menu->getItems()[0]['label']);
    }
}
