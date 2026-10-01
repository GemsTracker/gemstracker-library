<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use DateTimeImmutable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class MessengerBatchRepository
{
    public function __construct(
        private readonly BatchStoreInterface $batchStore,
        private readonly MessageBusInterface $messageBus,
    )
    {
    }

    public function createBatch(string|null $name = null, string|null $group = null, bool $isChain = false): Batch
    {
        $newId = Uuid::v4();

        return new Batch(
            batchId: $newId->toRfc4122(),
            created: new DateTimeImmutable(),
            name: $name,
            group: $group,
            isChain: $isChain,
        );
    }

    public function count(string $batchId): int
    {
        return $this->batchStore->count($batchId);
    }

    public function getBatch(string $batchId): Batch|null
    {
        return $this->batchStore->getBatch($batchId);
    }

    public function getBatchInfoList(string $batchId): array
    {
        return $this->batchStore->getBatchInfoList($batchId);
    }

    public function getBatchIterationMessage(string $batchId, int $iteration): object|null
    {
        return $this->batchStore->getBatchIterationMessage($batchId, $iteration);
    }

    public function dispatch(Batch $batch): void
    {
        $currentCount = $this->batchStore->count($batch->batchId);

        if (!count($batch->getCurrentMessages())) {
            return;
        }

        $this->batchStore->save($batch);

        if (!$batch->isChain) {
            $this->dispatchMessages($batch->getCurrentMessages(), $batch->batchId, $currentCount);
            $batch->clearMessages();
            return;
        }

        if ($this->batchStore->hasPending($batch->batchId) && !$this->batchStore->isRunning($batch->batchId)) {
            $messages = $batch->getCurrentMessages();
            $firstMessage = reset($messages);
            $this->dispatchMessages([$firstMessage], $batch->batchId, $currentCount);
            $batch->clearMessages();
            return;
        }
    }

    private function dispatchMessages(array $messages, string $batchId, int $iteratorOffset = 0): void
    {
        foreach($messages as $message) {
            $iteratorOffset++;
            $this->messageBus->dispatch($message, [
                new BatchStamp($batchId, $iteratorOffset),
            ]);
        }
    }

    public function failChain(string $batchId, int $failedIteration): void
    {
        $this->batchStore->failChain($batchId, $failedIteration);
    }

    public function setIterationFinished(string $batchId, int $iteration): void
    {
        $this->setIterationStatus($batchId, $iteration, BatchStatus::SUCCESS);
    }

    public function setIterationStatus(string $batchId, int $iteration, BatchStatus $status, string|null $message = null): void
    {
        $this->batchStore->setIterationStatus($batchId, $iteration, $status, $message);
    }
}