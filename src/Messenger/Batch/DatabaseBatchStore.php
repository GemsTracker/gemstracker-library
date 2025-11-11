<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use DateTimeImmutable;
use Exception;
use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Predicate\Predicate;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DatabaseBatchStore implements BatchStoreInterface
{
    private const DATETIME_STORAGE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly ResultFetcher $resultFetcher,
    )
    {
    }

    public function count(string $batchId): int
    {
        return $this->resultFetcher->fetchOne('SELECT count(*) FROM gems__batch WHERE gba_id = ?', [$batchId]);
    }

    public function exists(string $batchId): bool
    {
        return $this->count($batchId) > 0;
    }


    public function getBatch(string $batchId): Batch|null
    {
        $query = "
        SELECT 
            gba_id,
            gba_name,
            gba_group,
            gba_synchronous,
            COUNT(*) AS total_items,
            SUM(CASE WHEN gba_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN gba_status = 'running' THEN 1 ELSE 0 END) AS running_count,
            SUM(CASE WHEN gba_status = 'success' THEN 1 ELSE 0 END) AS success_count,
            SUM(CASE WHEN gba_status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
            MIN(gba_created) AS created_at,
            MAX(gba_finished) AS finished_at
        FROM gems__batch
        WHERE gba_id = ?
        GROUP BY gba_id, gba_name, gba_group, gba_synchronous;
        ";

        $data = $this->resultFetcher->fetchRow($query, [$batchId]);

        if (!$data) {
            return null;
        }

        $finished = null;
        if ((int)$data['total_items'] === (int)$data['success_count']) {
            $finished = new DateTimeImmutable($data['finished_at']);
        }

        return new Batch(
            batchId: $data['gba_id'],
            created: new DateTimeImmutable($data['created_at']),
            name: $data['gba_name'],
            group: $data['gba_group'],
            isChain: (bool)$data['gba_synchronous'],
            finished: $finished,
            totalItems: (int)$data['total_items'],
            pending: (int)$data['pending_count'],
            running: (int)$data['running_count'],
            success: (int)$data['success_count'],
            failed: (int)$data['failed_count'],
        );
    }

    public function getBatchInfoList(string $batchId): array
    {
        return $this->resultFetcher->fetchCol('SELECT gba_info FROM gems__batch WHERE gba_id = ? ORDER BY gba_iteration', [$batchId]);
    }

    public function getBatchIterationMessage(string $batchId, int $iteration): object|null
    {
        $data = $this->resultFetcher->fetchRow('SELECT * FROM gems__batch WHERE gba_id = ? AND gba_iteration = ?', [$batchId, $iteration]);

        if (!$data || !isset($data['gba_message'])) {
            return null;
        }

        $serializer = $this->getSerializer();
        try {
            return $serializer->deserialize($data['gba_message'], $data['gba_message_class'], 'json');
        } catch(Exception $e) {
            $this->setIterationStatus($batchId, $iteration, BatchStatus::FAILED, $e->getMessage());
            return null;
        }
    }

    public function hasPending(string $batchId): bool
    {
        return $this->resultFetcher->fetchOne('SELECT count(*) FROM gems__batch WHERE gba_id = ? AND gba_status = ?', [
            $batchId,
            BatchStatus::PENDING->value,
        ]) > 0;
    }

    public function isRunning(string $batchId): bool
    {
        return $this->resultFetcher->fetchOne('SELECT count(*) FROM gems__batch WHERE gba_id = ? AND gba_status = ?', [
                $batchId,
                BatchStatus::RUNNING->value,
            ]) > 0;
    }

    public function save(Batch $batch): void
    {
        $values = [
            'gba_id' => $batch->batchId,
            'gba_created' => $batch->created->format(self::DATETIME_STORAGE_FORMAT),
            'gba_name' => $batch->name,
            'gba_group' => $batch->group,
            'gba_status' => BatchStatus::PENDING->value,
            'gba_synchronous' => (int)$batch->isChain,
        ];

        $messages = $batch->getCurrentMessages();
        $serializer = $this->getSerializer();

        $i = $this->count($batch->batchId);
        foreach($messages as $message) {
            $i++ ;
            $values['gba_iteration'] = $i;
            $values['gba_message'] = $serializer->serialize($message, 'json');
            $values['gba_message_class'] = $message::class;
            $this->resultFetcher->insertIntoTable('gems__batch', $values);
        }
    }

    private function getSerializer(): Serializer
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        return new Serializer($normalizers, $encoders);
    }

    public function setIterationStatus(string $batchId, int $iteration, BatchStatus $status, string|null $message = null): void
    {
        $statusInfo = [
            'gba_status' => $status->value,
            'gba_info' => $message,
        ];

        if ($status === BatchStatus::RUNNING) {
            $statusInfo['gba_started'] = (new DateTimeImmutable())->format(self::DATETIME_STORAGE_FORMAT);
        }

        if ($status === BatchStatus::SUCCESS) {
            $statusInfo['gba_finished'] = (new DateTimeImmutable())->format(self::DATETIME_STORAGE_FORMAT);
        }

        $this->resultFetcher->updateTable('gems__batch', $statusInfo, [
            'gba_id' => $batchId,
            'gba_iteration' => $iteration,
        ]);
    }

    public function failChain(string $batchId, int $failedIteration): void
    {
        $statusInfo = [
            'gba_status' => BatchStatus::FAILED,
            'gba_info' => 'Chain failed due to iteration ' . $failedIteration,
        ];

        $this->resultFetcher->updateTable('gems__batch', $statusInfo, [
            'gba_id' => $batchId,
            'gba_status' => BatchStatus::PENDING->value,
            (new Predicate())->greaterThan('gba_iteration', $failedIteration),
        ]);
    }
}