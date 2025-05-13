<?php

namespace Gems\Export\Db;

class DbExportExtractor implements DataExtractorInterface
{
    public function extractData(array $row): array
    {
        $data = json_decode($row['gfex_data'] ?? '{}', true);
        $columnOrder = json_decode($row['gfex_column_order'], true);
        $orderedData = [];
        foreach($columnOrder as $columnName) {
            $orderedData[$columnName] = $data[$columnName] ?? null;
        }
        return $orderedData;
    }
}