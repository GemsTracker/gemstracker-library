<?php

namespace Gems\Util\Lock\Storage;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;

class DatabaseLock extends LockStorageAbstract
{
    protected readonly int $currentUserId;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    protected function calculateExpiresAt(DateInterval|int|null $expiresAfter=null): DateTimeInterface|null
    {
        if ($expiresAfter === null) {
            return null;
        }
        if (is_int($expiresAfter)) {
            $expiresAfter = new DateInterval("PT{$expiresAfter}S");
        }

        return (new DateTimeImmutable())->add($expiresAfter);
    }

    public function isLocked(): bool
    {
        $result = $this->getLockSetting();
        if (!$result) {
            return false;
        }
        if ((int)$result['glock_is_locked'] === 0) {
            return false;
        }
        if ($result['glock_locked_until'] === null) {
            return true;
        }

        $expiresAt = new DateTimeImmutable($result['glock_locked_until']);
        if ($expiresAt <= new DateTimeImmutable()) {
            $this->unlock();
            return false;
        }

        return true;
    }

    protected function getLockSetting(): ?array
    {
        $select = $this->resultFetcher->getSelect('gems__locks');
        $select->columns([
            'glock_is_locked',
            'glock_locked_until',
        ])->where([
            'glock_key' => $this->key,
        ]);
        return $this->resultFetcher->fetchRow($select);
    }

    public function getLockTime(): ?DateTimeInterface
    {
        $select = $this->resultFetcher->getSelect('gems__locks');
        $select->columns([
            'glock_changed',
        ])->where([
            'glock_key' => $this->key,
            'glock_is_locked' => 1,
        ]);
        $result = $this->resultFetcher->fetchOne($select);
        if ($result) {
            return new DateTimeImmutable($result);
        }
        return null;
    }

    protected function hasLockSetting(): ?bool
    {
        if ($this->getLockSetting()) {
            return true;
        }
        return false;
    }

    public function lock(DateInterval|int|null $expiresAfter=null): void
    {
        $lockedUntil = $this->calculateExpiresAt($expiresAfter);

        $query = 'INSERT INTO gems__locks (glock_key, glock_is_locked, glock_locked_until, glock_changed_by, glock_created_by)
            VALUES (?, 1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                glock_is_locked = 1,
                glock_locked_until = ?,
                glock_changed_by = ?';


        $lockUntilFormatted = $lockedUntil->format('Y-m-d H:i:s');

        $this->resultFetcher->query($query, [
            $this->key,
            $lockUntilFormatted,
            $this->currentUserId,
            $this->currentUserId,
            $lockUntilFormatted,
            $this->currentUserId,
        ]);
    }

    public function unlock(): void
    {
        $this->resultFetcher->updateTable('gems__locks', [
            'glock_is_locked' => 0,
        ],
        [
            'glock_key' => $this->key
        ]);
    }
}