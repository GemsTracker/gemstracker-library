<?php

namespace Gems\Export\Type;

use Iterator;
use Gems\Export\Db\DataExtractorInterface;

interface DownloadableInterface
{
    public function downloadFile(Iterator $iterator, DataExtractorInterface $extractor, string $exportId, string $fileName, array $exportSettings): array;
}