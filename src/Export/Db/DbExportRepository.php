<?php

namespace Gems\Export\Db;

use Gems\Db\ResultFetcher;
use Gems\Messenger\Message\Export\ModelExportPart;

class DbExportRepository
{
    public const EXPORT_DB = 'gems__file_exports';

    public function __construct(
        protected readonly ExportRepository $exportRepository,
        protected readonly ResultFetcher $resultFetcher,
    )
    {}
    public function insertDbPart(ModelExportPart $part): void
    {
        $data = $this->exportRepository->getRowData($part);

        $this->addHeaderToExportData($part);
        $this->addDataToExportData($part, $data);
    }

    protected function addDataToExportData(ModelExportPart $part, array $data): void
    {
        $i = (($part->part - 1) * $part->itemCount) + 1;
        foreach($data as $row) {
            $this->addRowToExportData($part, $i, $row);
            $i++;
        }
    }

    protected function addHeaderToExportData(ModelExportPart $part): void
    {
        $header = $this->exportRepository->getHeaders($part);
        $this->addRowToExportData($part, 0, $header);
    }

    protected function addRowToExportData(ModelExportPart $part, int $order, array $row): void
    {
        $this->resultFetcher->insertIntoTable(static::EXPORT_DB, [
            'gfex_export_id' => $part->exportId,
            'gfex_id_user' => $part->userId,
            'gfex_order' => $order,
            'gfex_data' => json_encode($row),
            'gfex_row_count' => $part->totalRows,
            'gfex_file_name' => $part->filename,
            'gfex_export_type' => $part->exportType,
        ]);
    }

    protected function getFilename(ModelExportPart $part)
    {

    }
}