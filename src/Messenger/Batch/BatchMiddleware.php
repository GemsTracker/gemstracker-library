<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class BatchMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessengerBatchRepository $batchRepository,
        private readonly ContainerInterface $container,
    )
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var BatchStamp|null $stamp */
        $stamp = $envelope->last(BatchStamp::class);
        if (!$stamp) {
            return $stack->next()->handle($envelope, $stack);
        }

        try {
            $this->batchRepository->setIterationStatus($stamp->batchId, $stamp->iteration, BatchStatus::RUNNING);

            $envelope = $stack->next()->handle($envelope, $stack);
            $handledStamp =$envelope->last(HandledStamp::class);
            $result = $handledStamp?->getResult();

            $message = null;
            if (is_string($result)) {
                $message = $result;
            }

            $batch = $this->batchRepository->getBatch($stamp->batchId);
            $this->batchRepository->setIterationStatus($stamp->batchId, $stamp->iteration, BatchStatus::SUCCESS, $message);

            if ($batch->isChain) {
                $nextMessage = $this->batchRepository->getBatchIterationMessage($stamp->batchId, $stamp->iteration + 1);
                if ($nextMessage) {
                    /**
                     * @var MessageBusInterface $messageBus
                     */
                    $messageBus = $this->container->get(MessageBusInterface::class);
                    $messageBus->dispatch($nextMessage, [
                        new BatchStamp($stamp->batchId, $stamp->iteration + 1),
                    ]);
                }
            }


        } catch (\Throwable $e) {
            $this->batchRepository->setIterationStatus(
                $stamp->batchId,
                $stamp->iteration,
                BatchStatus::FAILED,
                $e->getMessage()
            );

            throw $e;
        }

        return $envelope;
    }
}