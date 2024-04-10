<?php

namespace Gems\Export\Type;

use Gems\Export\Db\DataExtractorInterface;

interface DownloadableInterface
{
    public function downloadFile(iterable $iterator, DataExtractorInterface $extractor, string $exportId, string $fileName, array $exportSettings): array;
}