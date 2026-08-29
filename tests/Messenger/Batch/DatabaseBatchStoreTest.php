<?php

namespace GemsTest\Messenger\Batch;

use DateTimeImmutable;
use Gems\Messenger\Batch\Batch;
use Gems\Messenger\Batch\BatchStatus;
use Gems\Messenger\Batch\DatabaseBatchStore;
use GemsTest\testUtils\DatabaseTestCase;

class DatabaseBatchStoreTest extends DatabaseTestCase
{
    protected array $dbTables = [
        'gems__batch',
    ];

    private function getStore(): DatabaseBatchStore
    {
        return new DatabaseBatchStore($this->resultFetcher);
    }

    public function testSave(): void
    {
        $batchId = '550e8400-e29b-41d4-a716-446655440000';

        $store = $this->getStore();
        $count = $store->count($batchId);
        $this->assertEquals(0, $count);

        $batch = new Batch(
            batchId: $batchId,
            created: new DateTimeImmutable(),
        );
        $batch->addMessage(new TestMessage('test123'));
        $store->save($batch);

        $count = $store->count($batchId);
        $this->assertEquals(1, $count);

        $this->assertTrue($store->exists($batchId));
    }

    public function testGetBatchIterationMessage(): void
    {
        $batchId = '550e8400-e29b-41d4-a716-446655440000';

        $store = $this->getStore();

        $batch = new Batch(
            batchId: $batchId,
            created: new DateTimeImmutable(),
        );
        $batch->addMessage(new TestMessage('test123'));
        $store->save($batch);

        $message = $store->getBatchIterationMessage($batchId, 1);

        $this->assertInstanceOf(TestMessage::class, $message);

        $this->assertEquals('test123', $message->name);
    }

    public function testExists(): void
    {
        $batchId = '550e8400-e29b-41d4-a716-446655440000';

        $store = $this->getStore();

        $batch = new Batch(
            batchId: $batchId,
            created: new DateTimeImmutable(),
        );
        $batch->addMessage(new TestMessage('test123'));
        $store->save($batch);

        $this->assertTrue($store->exists($batchId));
    }

    public function testGetBatchInfoList(): void
    {
        $batchId = '550e8400-e29b-41d4-a716-446655440000';

        $store = $this->getStore();

        $batch = new Batch(
            batchId: $batchId,
            created: new DateTimeImmutable(),
        );
        $batch->addMessages([
            new TestMessage('test123'),
            new TestMessage('test123'),
            new TestMessage('test123'),
        ]);
        $store->save($batch);

        $store->setIterationStatus($batchId, 1, BatchStatus::SUCCESS, 'iteration 1 successful');
        $store->setIterationStatus($batchId, 2, BatchStatus::SUCCESS, 'iteration 2 successful');
        $store->setIterationStatus($batchId, 3, BatchStatus::FAILED, 'iteration 3 failed');

        $result = $store->getBatchInfoList($batchId);

        $expected = [
            'iteration 1 successful',
            'iteration 2 successful',
            'iteration 3 failed',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testIsPendingAndRunning(): void
    {
        $batchId = '550e8400-e29b-41d4-a716-446655440000';

        $store = $this->getStore();

        $batch = new Batch(
            batchId: $batchId,
            created: new DateTimeImmutable(),
        );
        $batch->addMessages([
            new TestMessage('test123'),
            new TestMessage('test123'),
            new TestMessage('test123'),
        ]);
        $store->save($batch);

        $this->assertTrue($store->hasPending($batchId));

        $store->setIterationStatus($batchId, 1, BatchStatus::RUNNING);

        $this->assertTrue($store->isRunning($batchId));
    }
}