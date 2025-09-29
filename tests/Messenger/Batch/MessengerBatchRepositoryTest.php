<?php

namespace GemsTest\Messenger\Batch;

use Gems\Messenger\Batch\DatabaseBatchStore;
use Gems\Messenger\Batch\MessengerBatchRepository;
use GemsTest\testUtils\DatabaseTestCase;
use Symfony\Component\Messenger\MessageBus;

class MessengerBatchRepositoryTest extends DatabaseTestCase
{
    protected array $dbTables = [
        'gems__batch',
    ];

    private function getRepository(): MessengerBatchRepository
    {
        $batchStore = new DatabaseBatchStore($this->resultFetcher);

        $messengerBus = new MessageBus();


        return new MessengerBatchRepository($batchStore, $messengerBus);
    }

    public function testCreateBatch(): void
    {
        $repository = $this->getRepository();
        $batch = $repository->createBatch('testBatch');

        $this->assertEquals('testBatch', $batch->name);
        $this->assertEquals(36, strlen($batch->batchId));
    }

    public function testSaveBatch(): void
    {
        $repository = $this->getRepository();
        $batch = $repository->createBatch('testBatch');

        $batch->addMessage(new TestMessage('hi'));

        $repository->dispatch($batch);

        $result = $repository->count($batch->batchId);
        $this->assertEquals(1, $result);


    }
}