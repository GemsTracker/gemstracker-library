<?php

namespace GemsTest\Messenger\Batch;

use Gems\Messenger\Batch\BatchStamp;
use Gems\Messenger\Batch\DatabaseBatchStore;
use Gems\Messenger\Batch\MessengerBatchRepository;
use GemsTest\testUtils\DatabaseTestCase;
use Symfony\Component\Messenger\Envelope;
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

    public function testAsyncDispatch(): void
    {
        $batchStore = new DatabaseBatchStore($this->resultFetcher);

        $capturedEnvelopes = [];

        $messengerBus = $this->createMock(MessageBus::class);
        $messengerBus
            ->method('dispatch')
            ->willReturnCallback(function (object $message, array $stamps = []) use (&$capturedEnvelopes) {
                // If code passed a raw message, wrap it similarly to real bus behavior:
                $envelope = Envelope::wrap($message, $stamps);
                $capturedEnvelopes[] = $envelope;
                return $envelope;
            });

        $repository = new MessengerBatchRepository($batchStore, $messengerBus);
        $batch = $repository->createBatch('testBatch');

        $batch->addMessages([
            new TestMessage('test123'),
            new TestMessage('test456'),
        ]);

        $repository->dispatch($batch);

        $this->assertCount(2, $capturedEnvelopes);
        $stamp = $capturedEnvelopes[0]->last(BatchStamp::class);
        $this->assertInstanceOf(BatchStamp::class, $stamp);
        $this->assertEquals(1, $stamp->iteration);
        $message = $capturedEnvelopes[0]->getMessage();
        $this->assertEquals('test123', $message->name);

        $stamp = $capturedEnvelopes[1]->last(BatchStamp::class);
        $this->assertInstanceOf(BatchStamp::class, $stamp);
        $this->assertEquals(2, $stamp->iteration);
        $message = $capturedEnvelopes[1]->getMessage();
        $this->assertEquals('test456', $message->name);
    }

    public function testSyncDispatch(): void
    {
        $batchStore = new DatabaseBatchStore($this->resultFetcher);

        $capturedEnvelopes = [];

        $messengerBus = $this->createMock(MessageBus::class);
        $messengerBus
            ->method('dispatch')
            ->willReturnCallback(function ($arg) use (&$capturedEnvelopes) {
                // If code passed a raw message, wrap it similarly to real bus behavior:
                $envelope = $arg instanceof Envelope ? $arg : new Envelope($arg);
                $capturedEnvelopes[] = $envelope;
                return $envelope;
            });

        $repository = new MessengerBatchRepository($batchStore, $messengerBus);
        $batch = $repository->createBatch('testBatch', isChain: true);

        $batch->addMessages([
            new TestMessage('test123'),
            new TestMessage('test456'),
        ]);

        $repository->dispatch($batch);

        $this->assertCount(1, $capturedEnvelopes);
    }

    public function testAsyncMultiDispatch(): void
    {
        $batchStore = new DatabaseBatchStore($this->resultFetcher);

        $capturedEnvelopes = [];

        $messengerBus = $this->createMock(MessageBus::class);
        $messengerBus
            ->method('dispatch')
            ->willReturnCallback(function (object $message, array $stamps = []) use (&$capturedEnvelopes) {
                // If code passed a raw message, wrap it similarly to real bus behavior:
                $envelope = Envelope::wrap($message, $stamps);
                $capturedEnvelopes[] = $envelope;
                return $envelope;
            });

        $repository = new MessengerBatchRepository($batchStore, $messengerBus);
        $batch = $repository->createBatch('testBatch');

        $batch->addMessage(new TestMessage('test123'));
        $repository->dispatch($batch);

        $batch->addMessage(new TestMessage('test456'));
        $repository->dispatch($batch);

        $this->assertCount(2, $capturedEnvelopes);
        $stamp = $capturedEnvelopes[0]->last(BatchStamp::class);
        $this->assertInstanceOf(BatchStamp::class, $stamp);
        $this->assertEquals(1, $stamp->iteration);
        $message = $capturedEnvelopes[0]->getMessage();
        $this->assertEquals('test123', $message->name);

        $stamp = $capturedEnvelopes[1]->last(BatchStamp::class);
        $this->assertInstanceOf(BatchStamp::class, $stamp);
        $this->assertEquals(2, $stamp->iteration);
        $message = $capturedEnvelopes[1]->getMessage();
        $this->assertEquals('test456', $message->name);
    }
}