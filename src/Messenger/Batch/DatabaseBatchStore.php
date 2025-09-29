<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use DateTimeImmutable;
use Exception;
use Gems\Db\ResultFetcher;
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
        $data = $this->resultFetcher->fetchRow('SELECT * FROM gems__batch WHERE gba_id = ?', [$batchId]);

        if (!$data) {
            return null;
        }

        return new Batch(
            $data['gba_id'],
            new DateTimeImmutable($data['gba_created']),
            $data['gba_name'],
            $data['gba_group'],
            $data['gba_finished'] ? new DateTimeImmutable($data['gba_finished']) : null,
            BatchStatus::from($data['gba_status']),
            (bool)$data['gba_synchronous'],
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
            return null;
        }
    }

    public function isPending(string $batchId): bool
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
            'gba_status' => $batch->status->value,
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

    public function setIterationFinished(string $batchId, int $iteration, string|null $message = null): void
    {
        $this->resultFetcher->updateTable('gems__batch', [
            'gba_status' => BatchStatus::SUCCESS->value,
            'gba_finished' => (new DateTimeImmutable())->format(self::DATETIME_STORAGE_FORMAT),
            'gba_info' => $message,
        ], [
            'gba_id' => $batchId,
            'gba_iteration' => $iteration,
        ]);
    }

    public function setIterationStatus(string $batchId, int $iteration, BatchStatus $status, string|null $message = null): void
    {
        $completed = null;
        if ($status === BatchStatus::SUCCESS) {
            $completed = (new DateTimeImmutable())->format(self::DATETIME_STORAGE_FORMAT);
        }

        $this->resultFetcher->updateTable('gems__batch', [
            'gba_status' => $status->value,
            'gba_finished' => $completed,
            'gba_info' => $message,
        ], [
            'gba_id' => $batchId,
            'gba_iteration' => $iteration,
        ]);
    }
}