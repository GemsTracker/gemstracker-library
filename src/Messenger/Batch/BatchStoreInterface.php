<?php

namespace Gems\Messenger\Batch;

interface BatchStoreInterface
{
    public function clearOldBatches(\DateInterval|null $dateInterval = null): void;

    public function count(string $batchId): int;

    public function exists(string $batchId): bool;

    public function failChain(string $batchId, int $failedIteration): void;

    public function getBatch(string $batchId): Batch|null;

    public function getBatchInfoList(string $batchId): array;

    public function getBatchIterationMessage(string $batchId, int $iteration): object|null;

    public function hasPending(string $batchId): bool;

    public function isRunning(string $batchId): bool;

    public function save(Batch $batch): void;

    public function setIterationStatus(string $batchId, int $iteration, BatchStatus $status, string|null $message = null): void;




}