<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use Symfony\Component\Messenger\Stamp\StampInterface;

class BatchStamp implements StampInterface
{
    public function __construct(
        public readonly string $batchId,
        public readonly int $iteration,
    )
    {
    }
}