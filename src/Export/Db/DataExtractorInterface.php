<?php

namespace Gems\Export\Db;

interface DataExtractorInterface
{
    public function extractData(array $row): array;
}