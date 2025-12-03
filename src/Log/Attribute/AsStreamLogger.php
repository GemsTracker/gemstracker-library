<?php

namespace Gems\Log\Attribute;

use Attribute;
use Monolog\Level;

#[Attribute(\Attribute::TARGET_CLASS)]
class AsStreamLogger
{
    public function __construct(
        public readonly string $path,
        public readonly Level $level = Level::Debug,
        public readonly string|null $name = null,
    )
    {
    }
}