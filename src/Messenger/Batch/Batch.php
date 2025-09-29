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
        public readonly string|null $name = null,
        public readonly string|null $group = null,
        public readonly DateTimeInterface|null $finished = null,
        public readonly BatchStatus $status = BatchStatus::PENDING,
        public readonly bool $isChain = false
    )
    {
    }

    public function addMessage(object $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @param object[] $messages
     * @return void
     */
    public function addMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
    }

    /**
     * @return object[]
     */
    public function getCurrentMessages(): array
    {
        return $this->messages;
    }

    public function clearMessages(): void
    {
        $this->messages = [];
    }
}