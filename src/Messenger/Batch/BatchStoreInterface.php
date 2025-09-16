<?php

namespace Gems\Messenger\Batch;

interface BatchStoreInterface
{
    public function count(string $batchId): int;
    public function exists(string $batchId): bool;
    public function getBatch(string $batchId): Batch|null;

    public function getBatchIterationMessage(string $batchId, int $iteration): object|null;

    public function isPending(string $batchId): bool;

    public function isRunning(string $batchId): bool;

    public function save(Batch $batch): void;

    public function setIterationFinished(string $batchId, int $iteration): void;




}