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

        $dispatcher = $this->createMock(EventDispatcher::class);

        $menu = new Menu($translator, $dispatcher);

        $this->assertStringStartsWith('TRANS', $menu->getItems()[0]['label']);
    }

    public function testDispatchesEvent()
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->will($this->returnCallback(function ($event) use (&$invocations) {
            $invocations[$event::class] = 1 + ($invocations[$event::class] ?? 0);
            return $this;
        }));

        $menu = new Menu($translator, $dispatcher);
        $menu->getItems();

        $this->assertSame([
            MenuBuildItemsEvent::class => 1,
        ], $invocations);
    }
}
