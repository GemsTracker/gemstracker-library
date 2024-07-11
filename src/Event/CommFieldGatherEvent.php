<?php

namespace Gems\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CommFieldGatherEvent extends Event
{
    public function __construct(
        public readonly string $target,
        public readonly string $language,
        public readonly string|int|null $id = null,
        public readonly int|null $organizationId = null,
        public array|null $fields = null,
    )
    {}
}