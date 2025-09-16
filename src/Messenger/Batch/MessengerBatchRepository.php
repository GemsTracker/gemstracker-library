<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use DateTimeImmutable;
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

    public function getBatch(string $batchId): Batch|null
    {
        return $this->batchStore->getBatch($batchId);
    }

    public function getBatchIterationMessage(string $batchId, int $iteration): object|null
    {
        return $this->batchStore->getBatchIterationMessage($batchId, $iteration);
    }

    public function dispatch(Batch $batch): void
    {
        $this->batchStore->save($batch);

        if (!count($batch->getCurrentMessages())) {
            return;
        }

        if (!$batch->isChain) {
            $this->dispatchMessages($batch->getCurrentMessages());
            return;
        }

        if ($this->batchStore->isPending($batch->batchId) || !$this->batchStore->isRunning($batch->batchId)) {
            $messages = $batch->getCurrentMessages();
            $firstMessage = reset($messages);
            $this->messageBus->dispatch($firstMessage);
            return;
        }
    }

    private function dispatchMessages(array $messages): void
    {
        foreach($messages as $message) {
            $this->messageBus->dispatch($message);
        }
    }

    public function setIterationFinished(string $batchId, int $iteration): void
    {
        $this->batchStore->setIterationFinished($batchId, $iteration);
    }
}