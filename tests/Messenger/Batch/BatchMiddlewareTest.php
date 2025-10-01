<?php

declare(strict_types=1);

namespace GemsTest\Messenger\Batch;

use Gems\Messenger\Batch\BatchMiddleware;
use Gems\Messenger\Batch\BatchStamp;
use Gems\Messenger\Batch\BatchStatus;
use Gems\Messenger\Batch\DatabaseBatchStore;
use Gems\Messenger\Batch\MessengerBatchRepository;
use GemsTest\testUtils\DatabaseTestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class BatchMiddlewareTest extends DatabaseTestCase
{
    protected array $dbTables = [
        'gems__batch',
    ];

    private MessengerBatchRepository|null $repository = null;

    private function getBatchRepository(): MessengerBatchRepository
    {
        if (!$this->repository) {
            $this->repository = new MessengerBatchRepository(
                new DatabaseBatchStore($this->resultFetcher),
                new MessageBus(),
            );
        }

        return $this->repository;
    }

    private function getMiddleware(MessageBusInterface|null $messageBus = null): BatchMiddleware
    {
        if ($messageBus === null) {
            $messageBus = new MessageBus();
        }

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(MessageBusInterface::class)->willReturn($messageBus);
        $repository = $this->getBatchRepository();
        $container->method('get')->with(MessengerBatchRepository::class)->willReturn($repository);

        return new BatchMiddleware($container);
    }

    private function getStack(): StackInterface
    {
        $handler = new class {
            public function __invoke(TestMessage $testMessage): string
            {
                return $testMessage->name;
            }
        };
        $next = new class($handler) implements MiddlewareInterface {
            public function __construct(private $handler) {}

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                $result = ($this->handler)($envelope->getMessage());
                return $envelope->with(new HandledStamp(
                    $result,
                    get_class($this->handler)
                ));
            }
        };

        // Wrap it in a StackMiddleware so it behaves like Messenger’s stack
        return new StackMiddleware([$next]);
    }

    public function testNoChain(): void
    {
        $store = new DatabaseBatchStore($this->resultFetcher);
        $repository = $this->getBatchRepository();
        $batch = $repository->createBatch();

        $messages = [
            new TestMessage('test123'),
            new TestMessage('test456'),
        ];

        $batch->addMessages($messages);

        $store->save($batch);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $middleware = $this->getMiddleware($messageBus);

        $envelope = Envelope::wrap($messages[0], [new BatchStamp($batch->batchId, 1)]);

        $middleware->handle($envelope, $this->getStack());
    }

    public function testChain(): void
    {
        $store = new DatabaseBatchStore($this->resultFetcher);
        $repository = $this->getBatchRepository();
        $batch = $repository->createBatch(isChain: true);

        $messages = [
            new TestMessage('test123'),
            new TestMessage('test456'),
        ];

        $batch->addMessages($messages);

        $store->save($batch);

        $capturedEnvelopes = [];
        $messageBus = $this->createMock(MessageBus::class);
        $messageBus
            ->expects($this->once())->method('dispatch')
            ->willReturnCallback(function (object $message, array $stamps = []) use (&$capturedEnvelopes) {
                // If code passed a raw message, wrap it similarly to real bus behavior:
                $envelope = Envelope::wrap($message, $stamps);
                $capturedEnvelopes[] = $envelope;
                return $envelope;
            });

        $middleware = $this->getMiddleware($messageBus);

        $envelope = Envelope::wrap($messages[0], [new BatchStamp($batch->batchId, 1)]);

        $middleware->handle($envelope, $this->getStack());

        $this->assertCount(1, $capturedEnvelopes);
        $stamp = $capturedEnvelopes[0]->last(BatchStamp::class);
        $this->assertInstanceOf(BatchStamp::class, $stamp);
        $this->assertEquals(2, $stamp->iteration);
        $message = $capturedEnvelopes[0]->getMessage();
        $this->assertEquals('test456', $message->name);
    }

    public function testItMarksBatchAsFailedOnException()
    {
        $batchId = 'batch-123';
        $iteration = 1;

        $envelope = (new Envelope(new TestMessage('test123')))
            ->with(new BatchStamp($batchId, $iteration));

        $matcher = $this->exactly(2);
        $batchRepository = $this->createMock(MessengerBatchRepository::class);
        $batchRepository->expects($matcher)
            ->method('setIterationStatus')
            ->willReturnCallback(function ($batchId, $iteration, $status, $message = null) use ($matcher) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame('batch-123', $batchId);
                        $this->assertSame(1, $iteration);
                        $this->assertSame(BatchStatus::RUNNING, $status);
                        break;
                    case 2:
                        $this->assertSame('batch-123', $batchId);
                        $this->assertSame(1, $iteration);
                        $this->assertSame(BatchStatus::FAILED, $status);
                        $this->assertStringContainsString('Error', $message);
                        break;
                }
            });

        // Fake "next" middleware that throws
        $failingMiddleware = new class implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                throw new \RuntimeException('Error!');
            }
        };

        $stack = new StackMiddleware($failingMiddleware);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(MessengerBatchRepository::class)->willReturn($batchRepository);
        $middleware = new BatchMiddleware($container);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error!');

        $middleware->handle($envelope, $stack);
    }
}