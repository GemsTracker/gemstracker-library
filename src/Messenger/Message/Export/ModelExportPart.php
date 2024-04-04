<?php

namespace Gems\Messenger\Message\Export;

use Gems\Export\ExportSettings\ExportSettingsInterface;

class ModelExportPart
{
    public function __construct(
        public readonly string $exportId,
        public readonly string $filename,
        public readonly string $exportType,
        public readonly int $userId,
        public readonly string $modelClassName,
        public readonly array $applyFunctions,
        public readonly array $filter = [],
        public readonly int $itemCount = 500,
        public readonly int $part = 1,
        public readonly int $totalRows = 0,
        public readonly ExportSettingsInterface|null $exportSettings = null,
    )
    {
    }
}