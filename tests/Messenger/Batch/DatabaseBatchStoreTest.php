<?php

namespace GemsTest\Messenger\Batch;

use DateTimeImmutable;
use Gems\Messenger\Batch\Batch;
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


}