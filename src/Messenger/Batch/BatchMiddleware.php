<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class BatchMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessengerBatchRepository $batchRepository,
        private readonly MessageBusInterface $messageBus,
    )
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var BatchStamp|null $stamp */
        $stamp = $envelope->last(BatchStamp::class);

        $envelope = $stack->next()->handle($envelope, $stack);
        if ($stamp) {
            $batch = $this->batchRepository->getBatch($stamp->batchId);
            $this->batchRepository->setIterationFinished($stamp->batchId, $stamp->iteration);

            if ($batch->isChain) {
                $nextMessage = $this->batchRepository->getBatchIterationMessage($stamp->batchId, $stamp->iteration+1);
                if ($nextMessage) {
                    $this->messageBus->dispatch($nextMessage);
                }
            }
        }

        return $envelope;
    }
}