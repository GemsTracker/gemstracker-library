<?php

namespace Gems\Export\Type;

use Iterator;
use Gems\Export\Db\DataExtractorInterface;

interface StreamableInterface
{
    public function streamResult(Iterator $iterator, DataExtractorInterface $extractor, string $fileName, array $exportSettings): void;
}