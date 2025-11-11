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
        public readonly bool $isChain = false,
        public readonly DateTimeInterface|null $finished = null,
        public readonly int|null $totalItems = null,
        public readonly int|null $pending = null,
        public readonly int|null $running = null,
        public readonly int|null $success = null,
        public readonly int|null $failed = null,



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