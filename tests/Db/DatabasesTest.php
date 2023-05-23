<?php

namespace Db;

use Gems\Db\Databases;
use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class DatabasesTest extends TestCase
{
    use ProphecyTrait;

    public function testGetDefaultDatabase()
    {
        $adapterProphecy = $this->prophesize(Adapter::class);
        $adapter = $adapterProphecy->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Adapter::class)->willReturn(true);
        $containerProphecy->get(Adapter::class)->willReturn($adapter);

        $databases = new Databases($containerProphecy->reveal());

        $this->assertEquals($adapter, $databases->getDefaultDatabase());
        $this->assertEquals($adapter, $databases->getDatabase($databases::DEFAULT_DATABASE_NAME));
    }

    public function testGetNoneExistingDatabase()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $databases = new Databases($containerProphecy->reveal());

        $this->assertNull($databases->getDatabase('test'));
    }

    public function testGetDatabase()
    {
        $adapterProphecy = $this->prophesize(Adapter::class);
        $adapter = $adapterProphecy->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Databases::ALIAS_PREFIX . 'Test')->willReturn(true);
        $containerProphecy->get(Databases::ALIAS_PREFIX . 'Test')->willReturn($adapter);

        $databases = new Databases($containerProphecy->reveal());

        $this->assertEquals($adapter, $databases->getDatabase('test'));
    }

}