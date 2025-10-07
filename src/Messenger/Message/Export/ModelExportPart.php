<?php

namespace Gems\Messenger\Message\Export;

class ModelExportPart
{
    public function __construct(
        public readonly string $exportId,
        public readonly string $filename,
        public readonly string $schemaname,
        public readonly string $exportType,
        public readonly int $userId,
        public readonly string|int $modelIdentifier,
        public readonly array $applyFunctions,
        public readonly array $columnOrder,
        public readonly array $filter = [],
        public readonly array $post = [],
        public readonly int $itemCount = 500,
        public readonly int $part = 1,
        public readonly int $totalRows = 0,
        public readonly array|null $exportSettings = null,
    )
    {
    }
}