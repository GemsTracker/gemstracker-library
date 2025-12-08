<?php

declare(strict_types=1);

namespace GemsTest\Util\Lock;

use Gems\Legacy\CurrentUserRepository;
use Gems\Util\Lock\Storage\DatabaseLock;
use GemsTest\testUtils\DatabaseTestCase;

class DatabaseLockTest extends DatabaseTestCase
{
    protected array $dbTables = [
        'gems__locks',
    ];

    private function getLock(): DatabaseLock
    {
        $currentUserRepository = $this->createMock(CurrentUserRepository::class);
        $currentUserRepository->method('getCurrentUserId')->willReturn(1);
        return new DatabaseLock($this->resultFetcher, $currentUserRepository);
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