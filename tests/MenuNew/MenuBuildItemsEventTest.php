<?php

namespace GemsTest\MenuNew;

use Gems\Event\Application\MenuBuildItemsEvent;

class MenuBuildItemsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testCanAddItems()
    {
        $event = new MenuBuildItemsEvent(['a', 'b']);

        $event->addItems(['c']);

        $this->assertSame(['a', 'b', 'c'], $event->getItems());
    }
}
