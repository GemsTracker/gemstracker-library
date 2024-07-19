<?php

namespace Gems\Event;

use Symfony\Contracts\EventDispatcher\Event;

class RawCommFieldEvent extends Event
{
    public function __construct(
        public readonly string $type,
        public array|null $rawFields = [],
    )
    {
    }
}