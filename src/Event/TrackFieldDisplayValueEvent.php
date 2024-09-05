<?php

declare(strict_types=1);

namespace Gems\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TrackFieldDisplayValueEvent extends Event
{
    public function __construct(
        public readonly string $type,
        public readonly mixed $rawValue,
        public mixed $displayValue = null,
    )
    {}
}