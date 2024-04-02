<?php

namespace Gems\Messenger\Message\Export;

class ModelExportPart
{
    public function __construct(
        public readonly string $exportId,
        public readonly string $modelClassName,
        public readonly array $filter = [],
        public readonly int $itemCount = 500,
        public readonly int $part = 1,
        public readonly bool $translateHeaders = false,
        public readonly bool $translateValues = false,
    )
    {
    }
}