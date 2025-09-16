<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use DateTimeInterface;

class Batch
{
    /*
     * @var object[]
     */
    private array $messages = [];

    public function __construct(
        public readonly string $batchId,
        public readonly DateTimeInterface $created,
        public string|null $name = null,
        public string|null $group = null,
        public readonly DateTimeInterface|null $finished = null,
        public readonly BatchStatus $status = BatchStatus::PENDING,
        public bool $isChain = false
    )
    {
    }

    public function addMessage(object $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return object[]
     */
    public function getCurrentMessages(): array
    {
        return $this->messages;
    }
}