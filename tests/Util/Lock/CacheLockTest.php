<?php

declare(strict_types=1);

namespace GemsTest\Util\Lock;

use Gems\Cache\HelperAdapter;
use Gems\Util\Lock\Storage\CacheLock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheLockTest extends TestCase
{
    private function getLock(): CacheLock
    {
        $adapter = new ArrayAdapter();
        $cacheHelper = new HelperAdapter($adapter);
        return new CacheLock($cacheHelper);
    }

    public function testLock(): void
    {
        $lock = $this->getLock();
        $lock->setKey('test123');

        $this->assertFalse($lock->isLocked());

        $lock->lock(1);
        $this->assertTrue($lock->isLocked());
        sleep(2);
        $this->assertFalse($lock->isLocked());
    }
}