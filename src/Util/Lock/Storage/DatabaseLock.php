<?php

namespace Gems\Util\Lock\Storage;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\TableGateway\TableGateway;

class DatabaseLock extends LockStorageAbstract
{
    protected int $currentUserId;

    public function __construct(protected ResultFetcher $resultFetcher, CurrentUserRepository $currentUserRepository)
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    public function isLocked(): bool
    {
        $result = $this->getLockSetting();
        if ($result) {
            return (bool) $result;
        }
        return false;
    }

    protected function getLockSetting(): ?string
    {
        $select = $this->resultFetcher->getSelect('gems__settings');
        $select->columns([
            'gst_value',
        ])->where([
            'gst_key' => $this->key,
        ]);
        return $this->resultFetcher->fetchOne($select);
    }

    public function getLockTime(): ?DateTimeInterface
    {
        $select = $this->resultFetcher->getSelect('gems__settings');
        $select->columns([
            'gst_created',
        ])->where([
            'gst_key' => $this->key,
            'gst_value' => 1,
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

    public function lock(): void
    {
        $table = new TableGateway('gems__settings', $this->resultFetcher->getAdapter());
        $now = new DateTimeImmutable();

        if ($this->hasLockSetting()) {
            $table->update([
                'gst_value' => 1,
                'gst_changed' => $now->format('Y-m-d H:i'),
                'gst_changed_by' => $this->currentUserId,
            ],
                [
                    'gst_key' => $this->key
                ]);
        }
        $table->insert([
            'gst_key' => $this->key,
            'gst_value' => 1,
            'gst_changed' => $now->format('Y-m-d H:i'),
            'gst_changed_by' => $this->currentUserId,
            'gst_created' => $now->format('Y-m-d H:i'),
            'gst_created_by' => $this->currentUserId,
        ]);
    }

    public function unlock(): void
    {
        $table = new TableGateway('gems__settings', $this->resultFetcher->getAdapter());
        $now = new DateTimeImmutable();

        if ($this->hasLockSetting()) {
            $table->update([
                'gst_value' => 0,
                'gst_changed' => $now->format('Y-m-d H:i'),
                'gst_changed_by' => $this->currentUserId,
            ],
                [
                    'gst_key' => $this->key
                ]);
        }
        $table->insert([
            'gst_key' => $this->key,
            'gst_value' => 0,
            'gst_changed' => $now->format('Y-m-d H:i'),
            'gst_changed_by' => $this->currentUserId,
            'gst_created' => $now->format('Y-m-d H:i'),
            'gst_created_by' => $this->currentUserId,
        ]);
    }
}