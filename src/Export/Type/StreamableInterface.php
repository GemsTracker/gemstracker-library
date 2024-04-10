<?php

namespace Gems\Export\Type;

use Gems\Export\Db\DataExtractorInterface;

interface StreamableInterface
{
    public function streamResult(iterable $iterator, DataExtractorInterface $extractor, string $fileName, array $exportSettings): void;
}