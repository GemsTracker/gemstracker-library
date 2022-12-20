<?php

namespace Gems\Util;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Laminas\Db\TableGateway\TableGateway;
use DateTimeImmutable;

class MaintenanceLock
{
    protected int $currentUserId;

    protected string $key = 'maintenance-mode';

    public function __construct(protected ResultFetcher $resultFetcher, CurrentUserRepository $currentUserRepository)
    {
        $this->currentUserId = $currentUserRepository->getCurrentUser()->getUserId();
    }

    public function isLocked()
    {
        $result = $this->getMaintenanceModeSetting();
        if ($result) {
            return (bool) $result;
        }
        return false;
    }

    protected function getMaintenanceModeSetting()
    {
        $select = $this->resultFetcher->getSelect('gems__settings');
        $select->columns([
            'gst_value',
        ])->where([
            'gst_key' => $this->key,
        ]);
        return $this->resultFetcher->fetchOne($select);
    }

    protected function hasMaintenanceModeSetting(): ?bool
    {
        if ($this->getMaintenanceModeSetting()) {
            return true;
        }
        return false;
    }

    public function lock()
    {
        $table = new TableGateway('gems__settings', $this->resultFetcher->getAdapter());
        $now = new DateTimeImmutable();

        if ($this->hasMaintenanceModeSetting()) {
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

    public function unlock()
    {
        $table = new TableGateway('gems__settings', $this->resultFetcher->getAdapter());
        $now = new DateTimeImmutable();

        if ($this->hasMaintenanceModeSetting()) {
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